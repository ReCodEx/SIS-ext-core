<?php

namespace App\Helpers;

use App\Exceptions\RecodexApiException;
use JsonSerializable;
use Nette;

/**
 * Wrapper for Group data sent from ReCodEx API.
 */
class RecodexGroup implements JsonSerializable
{
    use Nette\SmartObject;

    // attribute keys used by this extension

    /**
     * Course identifier used for top-level ReCodEx groups
     */
    public const ATTR_COURSE_KEY = 'course';

    /**
     * Year identifier used for 2nd-level (semester) ReCodEx groups
     */
    public const ATTR_YEAR_KEY = 'year';

    /**
     * Key used for bindings with SIS student groups (GL identifiers)
     */
    public const ATTR_GROUP_KEY = 'group';

    /**
     * ReCodEx group ID
     */
    public string $id;

    /**
     * ReCodEx parent group ID
     */
    public ?string $parentGroupId;

    /**
     * List of group primary admins id => [ titlesBeforeName, firstName, lastName, titlesAfterName, email ]
     */
    public array $admins;

    /**
     * Group names indexed by locale identifiers
     */
    public array $name = [];

    /**
     * Group descriptions indexed by locale identifiers
     */
    public array $description = [];

    /**
     * Indicates whether the group is organizational (does not have assignments)
     */
    public bool $organizational;

    /**
     * Indicates whether the group is an exam group.
     */
    public bool $exam;

    /**
     * Indicates whether the group is public.
     */
    public bool $public;

    /**
     * Indicates whether the group is detaining (students cannot leave on their own).
     */
    public bool $detaining;

    /**
     * External attributes assigned by this extension.
     */
    public array $attributes;

    /**
     * Indicates the membership type of the logged in user to the group.
     * Possible values are: 'admin', 'supervisor', 'observer', and 'student'
     * (and null if there is no relation between the user and the group).
     */
    public ?string $membership = null;

    /**
     * Validates that admins array contains proper associative arrays with required keys
     * @param array $admins
     * @throws RecodexApiException if admins structure is invalid
     */
    private function validateAdminsStructure(array $admins): void
    {
        $requiredAdminKeys = ['titlesBeforeName', 'firstName', 'lastName', 'titlesAfterName', 'email'];

        foreach ($admins as $adminId => $adminData) {
            if (!is_array($adminData)) {
                throw new RecodexApiException(
                    "Admin with ID '$adminId' must be an associative array, " . gettype($adminData) . ' given'
                );
            }

            foreach ($requiredAdminKeys as $key) {
                if (!array_key_exists($key, $adminData)) {
                    throw new RecodexApiException(
                        "Admin with ID '$adminId' is missing required key '$key'"
                    );
                }
            }
        }
    }

    /**
     * Validates localizedTexts structure and transforms it into name/description arrays
     * @param array $localizedTexts
     * @return array Returns associative array with 'name' and 'description' keys containing locale-indexed arrays
     * @throws RecodexApiException if localizedTexts structure is invalid
     */
    private function processLocalizedTexts(array $localizedTexts): array
    {
        $requiredLocalizedTextKeys = ['locale', 'name', 'description'];
        $name = [];
        $description = [];

        foreach ($localizedTexts as $index => $localizedTextData) {
            if (!is_array($localizedTextData)) {
                throw new RecodexApiException(
                    "LocalizedText at index '$index' must be an associative array, " .
                        gettype($localizedTextData) . ' given'
                );
            }

            foreach ($requiredLocalizedTextKeys as $key) {
                if (!array_key_exists($key, $localizedTextData)) {
                    throw new RecodexApiException(
                        "LocalizedText at index '$index' is missing required key '$key'"
                    );
                }
            }

            // Transform into locale-indexed arrays
            $locale = $localizedTextData['locale'];
            $name[$locale] = $localizedTextData['name'];
            $description[$locale] = $localizedTextData['description'];
        }

        return ['name' => $name, 'description' => $description];
    }

    /**
     * @param array $data parsed JSON group view
     * @param string $attributesService name of the attributes service (this application's namespace)
     */
    public function __construct(array $data, string $attributesService)
    {
        // Validate all required keys are present in API response
        $requiredKeys = [
            'id',
            'parentGroupId',
            'admins',
            'localizedTexts',
            'organizational',
            'exam',
            'public',
            'detaining',
            'attributes',
            'membership'
        ];

        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $data)) {
                throw new RecodexApiException("Missing required key '$key' in group API response");
            }
        }

        // Validate admins array structure
        $this->validateAdminsStructure($data['admins']);

        // Process and validate localizedTexts array structure
        $localizedData = $this->processLocalizedTexts($data['localizedTexts']);

        // Initialize public members from the associative array (values can be null)
        $this->id = $data['id'];
        $this->parentGroupId = $data['parentGroupId'];
        $this->admins = $data['admins'];
        $this->name = $localizedData['name'];
        $this->description = $localizedData['description'];
        $this->organizational = $data['organizational'];
        $this->exam = $data['exam'];
        $this->public = $data['public'];
        $this->detaining = $data['detaining'];
        $this->attributes = $data['attributes'][$attributesService] ?? [];
        $this->membership = $data['membership'];
    }

    /**
     * Serializes the object to a value that can be serialized natively by json_encode().
     * @return array Data which can be serialized by json_encode
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'parentGroupId' => $this->parentGroupId,
            'admins' => $this->admins,
            'name' => $this->name,
            'description' => $this->description,
            'organizational' => $this->organizational,
            'exam' => $this->exam,
            'public' => $this->public,
            'detaining' => $this->detaining,
            'attributes' => $this->attributes,
            'membership' => $this->membership,
        ];
    }

    /**
     * Make sure all ancestor groups are included in the selection. The selected groups array is updated in place.
     * @param RecodexGroup[] $selectedGroups groups selected so far (id => RecodexGroup)
     * @param RecodexGroup[] $allGroups all available groups (id => RecodexGroup)
     */
    private static function ancestralClosure(array &$selectedGroups, array $allGroups): void
    {
        $toCheck = array_keys($selectedGroups);
        while ($toCheck) {
            $currentId = array_shift($toCheck);
            if (empty($allGroups[$currentId])) {
                continue;
            }

            $parentId = $allGroups[$currentId]->parentGroupId;
            if ($parentId && empty($selectedGroups[$parentId])) {
                $selectedGroups[$parentId] = $allGroups[$parentId];
                $toCheck[] = $parentId;
            }
        }
    }

    /**
     * Checks if the group belongs to any SIS group.
     * @param RecodexGroup $group The group to check.
     * @param array $sisGroupsIndex The index of SIS groups [ sisGroupId => unused value ].
     * @return bool True if the group belongs to any SIS group, false otherwise.
     */
    private static function belongsToSisGroup(RecodexGroup $group, array $sisGroupsIndex): bool
    {
        foreach ($group->attributes[self::ATTR_GROUP_KEY] ?? [] as $sisGrpId) {
            if (array_key_exists($sisGrpId, $sisGroupsIndex)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Prunes the group list for students, keeping only relevant groups.
     * Relevant are groups that belong to any SIS group or where the student already belongs to
     * (the ancestral closure of relevant groups is returned so hierarchical names can be displayed).
     * @param RecodexGroup[] $groups The list of groups to prune (indexed by group IDs).
     * @param array $sisGroups The list of SIS group IDs.
     * @return RecodexGroup[] The pruned list of groups (indexed by group IDs).
     */
    public static function pruneForStudent(array $groups, array $sisGroups): array
    {
        $sisGroupsIndex = array_flip($sisGroups);
        $pruned = [];
        foreach ($groups as $id => $group) {
            if (self::belongsToSisGroup($group, $sisGroupsIndex) || $group->membership === 'student') {
                $pruned[$id] = $group;
            }
        }

        self::ancestralClosure($pruned, $groups);
        return $pruned;
    }
}
