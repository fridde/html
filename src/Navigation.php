<?php

namespace Fridde;

use Fridde\Controller\LoginController;

class Navigation
{
    /** @var \Fridde\Naturskolan $N */
    private $N;

    public function __construct()
    {
        $this->N = $GLOBALS["CONTAINER"]->get("Naturskolan");
    }

    /**
     * @return string
     */
    public function getUserRole()
    {
        $login = new LoginController();
        $school_id = $login->getSchooldIdFromCookie();
        if ($school_id === "natu") {
            return "admin";
        } elseif (!empty($school_id)) {
            return "user";
        }

        return "guest";
    }

    public function getMenuForRole(string $role = 'guest')
    {
        $menu_items = self::getNavSettings($role);

        return array_map(
            function ($item) {
                $label = $item["label"];
                $children = $this->getChildItems($item["children"] ?? []);
                $url = $item['url'] ?? call_user_func([$this, $item["method"]]);

                return compact('label', 'url', 'children');
            },
            $menu_items
        );
    }

    private function getChildItems(array $children)
    {
        $items = [];
        if (!empty($children["method"])) {
            $items = $this->$children["method"];
        }
        $extra_items = $children["items"] ?? [];

        return array_merge($items, $extra_items);

    }

    public static function getNavSettings(string $key = null)
    {
        return SETTINGS["NAV_SETTINGS"][$key] ?? SETTINGS["NAV_SETTINGS"];

    }

    private function getSchoolPageUrl(string $page = 'groups')
    {
        $login = new LoginController();
        $params["school"] = $login->getSchooldIdFromCookie();
        $params["page"] = $page;

        return $this->N->generateUrl('school', $params);
    }

    private function getStaffPageUrl(): string
    {
        return $this->getSchoolPageUrl('staff');
    }

    private function getGroupsPageUrl(): string
    {
        return $this->getSchoolPageUrl('groups');
    }

    private function getAllSchoolUrls(): array
    {
        /* @var \Fridde\Entities\School[] $school_labels */
        $school_labels = $this->N->getRepo('School')->findAllSchoolLabels();

        return array_map(
            function ($id, $label) {
                $r["label"] = $label;
                $r["url"] = $this->N->generateUrl('school', ['school' => $id]);

                return $r;
            },
            array_keys($school_labels),
            $school_labels
        );
    }

    private function getAllTableUrls(): array
    {
        $configurable_tables = SETTINGS['admin']['table_menu_items'];

        return array_map(
            function ($table) {
                $r["label"] = $table;
                $r["url"] = $this->N->generateUrl('table', ['entity' => $table]);

                return $r;
            },
            $configurable_tables
        );

    }


}