<?php

namespace App\Presenters;

use App\Exceptions\ForbiddenRequestException;
use App\Exceptions\BadRequestException;
use App\Model\Entity\SisTerm;
use App\Model\Repository\SisTerms;
use App\Security\ACL\ITermPermissions;
use Nette\Application\Request;
use DateTime;

/**
 * User-related operations.
 */
class TermsPresenter extends BasePresenter
{
    /**
     * @var SisTerms
     * @inject
     */
    public $sisTerms;

    /**
     * @var ITermPermissions
     * @inject
     */
    public $termAcl;

    private function getDateTime(Request $req, string $name, ?DateTime $minTs, ?DateTime $maxTs): ?DateTime
    {
        $timestamp = (int)$req->getPost($name);
        if ($timestamp <= 0) {
            return null;
        }
        $res = DateTime::createFromFormat('U', $timestamp);
        if ($minTs && $res < $minTs) {
            throw new BadRequestException("DateTime '$name' must be older than " . $minTs->format('Y-m-d H:i:s'));
        }
        if ($maxTs && $maxTs < $res) {
            throw new BadRequestException("DateTime '$name' must not be older than " . $maxTs->format('Y-m-d H:i:s'));
        }
        return $res;
    }

    /**
     * Process term dates from the request and set them on the SisTerm entity.
     * @param SisTerm $term The term entity to update.
     * @param Request $req The request containing the date parameters.
     * @throws BadRequestException If the date parameters are invalid or inconsistent.
     */
    private function processTermDates(SisTerm $term, Request $req): void
    {
        $year = $term->getYear();
        $minTs = DateTime::createFromFormat('Y-m-d H:i:s', "$year-01-01 00:00:00");
        ++$year;
        $maxTs = DateTime::createFromFormat('Y-m-d H:i:s', "$year-12-31 23:59:59");

        $begin = $this->getDateTime($req, "beginning", $minTs, $maxTs);
        $end = $this->getDateTime($req, "end", $minTs, $maxTs);
        if ($begin || $end) {
            if (!$begin || !$end) {
                throw new BadRequestException("Both 'begin' and 'end' dates must be provided together.");
            }
            if ($begin > $end) {
                throw new BadRequestException("The 'begin' date cannot be after the 'end' date.");
            }
            $term->setBeginning($begin);
            $term->setEnd($end);
        } else {
            $term->setBeginning(null);
            $term->setEnd(null);
        }

        $studentsFrom = $this->getDateTime($req, "studentsFrom", $minTs, $maxTs);
        $studentsUntil = $this->getDateTime($req, "studentsUntil", $minTs, $maxTs);
        if (!$studentsFrom || !$studentsUntil) {
            throw new BadRequestException("Both 'studentsFrom' and 'studentsUntil' dates must be provided.");
        }
        if ($studentsFrom > $studentsUntil) {
            throw new BadRequestException("The 'studentsFrom' date cannot be after the 'studentsUntil' date.");
        }
        $term->setStudentsAdvertisement($studentsFrom, $studentsUntil);

        $teachersFrom = $this->getDateTime($req, "teachersFrom", $minTs, $maxTs);
        $teachersUntil = $this->getDateTime($req, "teachersUntil", $minTs, $maxTs);
        if (!$teachersFrom || !$teachersUntil) {
            throw new BadRequestException("Both 'teachersFrom' and 'teachersUntil' dates must be provided.");
        }
        if ($teachersFrom > $teachersUntil) {
            throw new BadRequestException("The 'teachersFrom' date cannot be after the 'teachersUntil' date.");
        }
        $term->setTeachersAdvertisement($teachersFrom, $teachersUntil);

        $archiveAfter = $this->getDateTime($req, "archiveAfter", $minTs, null);
        if ($archiveAfter) {
            if ($archiveAfter < $studentsUntil || $archiveAfter < $teachersUntil) {
                throw new BadRequestException(
                    "The 'archiveAfter' date should be after the advertisement periods for students and teachers."
                );
            }
            $term->setArchiveAfter($archiveAfter);
        } else {
            $term->setArchiveAfter(null);
        }
    }

    public function checkDefault()
    {
        if (!$this->termAcl->canList()) {
            throw new ForbiddenRequestException("You do not have permissions to list terms.");
        }
    }

    /**
     * Retrieve all SIS terms.
     * @GET
     */
    public function actionDefault()
    {
        $terms = $this->sisTerms->findAll();
        $this->sendSuccessResponse($terms);
    }

    public function checkCreate()
    {
        if (!$this->termAcl->canCreate()) {
            throw new ForbiddenRequestException("You do not have permissions to create terms.");
        }
    }

    /**
     * Create a new SIS term.
     * @POST
     * @Param(type="post", name="year", validation="numericint", required=true,
     *        description="Academic year in which the term begins, e.g. 2025.")
     * @Param(type="post", name="term", validation="numericint", required=true,
     *        description="Term number, 1 for winter term, 2 for summer term.")
     * @Param(type="post", name="studentsFrom", validation="numericint", required=true,
     *        description="From when the term should allow students to enroll in groups (unix ts).")
     * @Param(type="post", name="studentsUntil", validation="numericint", required=true,
     *        description="Till when the term should allow students to enroll in groups (unix ts).")
     * @Param(type="post", name="teachersFrom", validation="numericint", required=true,
     *        description="From when the term should allow teachers to create groups (unix ts).")
     * @Param(type="post", name="teachersUntil", validation="numericint", required=true,
     *        description="Till when the term should allow teachers to create groups (unix ts).")
     * @Param(type="post", name="beginning", validation="numericint", required=false,
     *        description="When the term officially begins (unix ts).")
     * @Param(type="post", name="end", validation="numericint", required=false,
     *        description="When the term officially ends (unix ts).")
     * @Param(type="post", name="archiveAfter", validation="numericint", required=false,
     *        description="When the archiving of groups should be suggested (unix ts).")
     */
    public function actionCreate()
    {
        $req = $this->getRequest();
        $year = (int)$req->getPost('year');
        if ($year < 2000 || $year > 2200) {
            throw new BadRequestException("Year must be between 2000 and 2200.");
            // if this app is still running in 2200, we have bigger problems :)
        }

        $term = (int)$req->getPost('term');
        if ($term < 1 || $term > 2) {
            throw new BadRequestException("Term must be either 1 (winter) or 2 (summer).");
        }

        if ($this->sisTerms->findTerm($year, $term)) {
            throw new BadRequestException("Term already exists.");
        }

        $sisTerm = new SisTerm($year, $term);
        $this->processTermDates($sisTerm, $req);

        $this->sisTerms->persist($sisTerm);
        $this->sendSuccessResponse($sisTerm);
    }

    public function checkDetail(string $id)
    {
        $term = $this->sisTerms->findOrThrow($id);
        if (!$this->termAcl->canViewDetail($term)) {
            throw new ForbiddenRequestException("You do not have permissions to term details.");
        }
    }

    /**
     * Retrieve detail of given term.
     * @GET
     * @param string $id of the SIS term
     */
    public function actionDetail(string $id)
    {
        $term = $this->sisTerms->findOrThrow($id);
        $this->sendSuccessResponse($term);
    }

    public function checkUpdate(string $id)
    {
        $term = $this->sisTerms->findOrThrow($id);
        if (!$this->termAcl->canUpdate($term)) {
            throw new ForbiddenRequestException("You do not have permissions to update this term.");
        }
    }

    /**
     * Update SIS term data.
     * @POST
     * @Param(type="post", name="studentsFrom", validation="numericint", required=true,
     *        description="From when the term should allow students to enroll in groups (unix ts).")
     * @Param(type="post", name="studentsUntil", validation="numericint", required=true,
     *        description="Till when the term should allow students to enroll in groups (unix ts).")
     * @Param(type="post", name="teachersFrom", validation="numericint", required=true,
     *        description="From when the term should allow teachers to create groups (unix ts).")
     * @Param(type="post", name="teachersUntil", validation="numericint", required=true,
     *        description="Till when the term should allow teachers to create groups (unix ts).")
     * @Param(type="post", name="beginning", validation="numericint", required=false,
     *        description="When the term officially begins (unix ts).")
     * @Param(type="post", name="end", validation="numericint", required=false,
     *        description="When the term officially ends (unix ts).")
     * @Param(type="post", name="archiveAfter", validation="numericint", required=false,
     *        description="When the archiving of groups should be suggested (unix ts).")
     */
    public function actionUpdate(string $id)
    {
        $req = $this->getRequest();
        $sisTerm = $this->sisTerms->findOrThrow($id);
        $this->processTermDates($sisTerm, $req);
        $this->sisTerms->persist($sisTerm);
        $this->sendSuccessResponse($sisTerm);
    }

    public function checkRemove(string $id)
    {
        $term = $this->sisTerms->findOrThrow($id);
        if (!$this->termAcl->canRemove($term)) {
            throw new ForbiddenRequestException("You do not have permissions to remove this term.");
        }
    }

    /**
     * Remove SIS term.
     * @DELETE
     */
    public function actionRemove(string $id)
    {
        $term = $this->sisTerms->findOrThrow($id);
        $this->sisTerms->remove($term);
        $this->sendSuccessResponse("OK");
    }
}
