<?php

namespace App\Presenters;

use App\Exceptions\BadRequestException;
use App\Exceptions\ForbiddenRequestException;
use App\Model\Entity\SisAffiliation;
use App\Model\Entity\SisTerm;
use App\Model\Entity\User;
use App\Model\Repository\SisAffiliations;
use App\Model\Repository\SisCourses;
use App\Model\Repository\SisScheduleEvents;
use App\Model\Repository\SisTerms;
use App\Model\Repository\Users;
use App\Helpers\SisHelper;
use App\Security\ACL\ITermPermissions;
use DateTime;

/**
 * Course-related operations (listing SIS courses and scheduling events).
 */
class CoursesPresenter extends BasePresenter
{
    /**
     * @var SisHelper
     * @inject
     */
    public $sis;

    /**
     * @var SisAffiliations
     * @inject
     */
    public $sisAffiliations;

    /**
     * @var SisCourses
     * @inject
     */
    public $sisCourses;

    /**
     * @var SisScheduleEvents
     * @inject
     */
    public $sisScheduleEvents;

    /**
     * @var SisTerms
     * @inject
     */
    public $sisTerms;

    /**
     * @var Users
     * @inject
     */
    public $users;

    /**
     * @var ITermPermissions
     * @inject
     */
    public $termAcl;

    /**
     * Get the current SIS term from the request parameters.
     */
    private function getTerm(): SisTerm
    {
        $req = $this->getRequest();
        $year = (int)$req->getPost('year');
        $term = (int)$req->getPost('term');
        $sisTerm = $this->sisTerms->findTerm($year, $term);
        if (!$sisTerm) {
            throw new BadRequestException("Term $year-$term not found.");
        }
        return $sisTerm;
    }

    private function getAffiliations(SisTerm $term): array
    {
        $req = $this->getRequest();
        $affiliation = $req->getPost('affiliation');
        $canStudent = $this->termAcl->canViewStudentCourses($term);
        if ($affiliation === 'student' && !$canStudent) {
            throw new ForbiddenRequestException("You are not allowed to view student courses.");
        }
        $canTeacher = $this->termAcl->canViewTeacherCourses($term);
        if ($affiliation === 'teacher' && !$canTeacher) {
            throw new ForbiddenRequestException("You are not allowed to view teacher courses.");
        }

        $res = [];
        if ($canStudent && (!$affiliation || $affiliation === 'student')) {
            $res[] = SisAffiliation::TYPE_STUDENT;
        }
        if ($canTeacher && (!$affiliation || $affiliation === 'teacher')) {
            // at the moment, the UI does not make a distinction between teachers and guarantors
            $res[] = SisAffiliation::TYPE_TEACHER;
            $res[] = SisAffiliation::TYPE_GUARANTOR;
        }
        return $res;
    }

    /**
     * Check the expiration date whether the data needs to be re-fetched.
     * @return bool true if re-fetching is needed
     */
    private function isRefetchNeeded(User $user): bool
    {
        $expiration = $this->getRequest()->getPost('expiration');
        if ($expiration === null) {
            return false;
        }

        $expiration = (int)$expiration;
        $threshold = new DateTime();
        if ($expiration > 0) {
            $threshold->modify("-$expiration day");
        }

        return $user->getSisEventsLoaded() === null || $user->getSisEventsLoaded() < $threshold;
    }

    /**
     * Load courses from SIS and update local DB entities.
     */
    private function refetchSisCourses(User $user): void
    {
        // find active terms and create mapping termId => SisTerm
        $terms = [];
        foreach ($this->sisTerms->findAllActive() as $term) {
            $key = sprintf("%s-%s", $term->getYear(), $term->getTerm());
            $terms[$key] = $term;

            // we need to clear current affiliations to reflect when students' get unenrolled from courses
            $this->sisAffiliations->clearAffiliations($user, $term);
        }

        foreach ($this->sis->getCourses($user->getSisId(), array_keys($terms)) as $course) {
            if (!array_key_exists($course->getTermIdentifier(), $terms)) {
                continue; // superfluous data sent over from sis, skipping
            }

            $course->updateLocalCourseAndAffiliations(
                $this->sisCourses,
                $this->sisScheduleEvents,
                $this->sisAffiliations,
                $this->users,
                $terms[$course->getTermIdentifier()],
            );
        }

        $user->setSisEventsLoaded();
        $this->users->persist($user);
    }

    /**
     * Return all scheduling events of given semester that apply to logged in user.
     * The events are filtered by user affiliation and term.
     * The expiration parameter controls whether the data needs to be re-fetched from SIS
     * (otherwise the cached data from local DB is used).
     * @POST
     * @Param(type="post", name="year", validation="numericint", required=true,
     *        description="Academic year to fetch events from.")
     * @Param(type="post", name="term", validation="numericint", required=true,
     *        description="Semester to fetch events from (1=winter, 2=summer).")
     * @Param(type="post", name="affiliation", validation="string", required=false,
     *        description="Either 'student' (courses for enrollment) or 'teacher' (courses for creating groups).
     *                     If not provided, all eligible courses are fetched.")
     * @Param(type="post", name="expiration", validation="numericint", required=false,
     *        description="How long since the last fetch (in days) is tolerated before a refetch is performed.
     *                     No expiration, no refetching is performed; zero means forced refetching.")
     */
    public function actionDefault()
    {
        $user = $this->getCurrentUser();

        $term = $this->getTerm(); // based on current request (year, term)
        $affiliations = $this->getAffiliations($term); // based on current request (affiliation)
        if (!$affiliations) { // no affiliations, no results
            $this->sendSuccessResponse([]);
        }

        $res = [];
        if ($this->isRefetchNeeded($user)) {
            $this->refetchSisCourses($user);
            $res['refetched'] = true;
        }

        foreach ($affiliations as $affiliation) {
            $res[$affiliation] = $this->sisScheduleEvents->allEventsOfUser($user, $term, $affiliation);
        }

        $this->sendSuccessResponse($res);
    }
}
