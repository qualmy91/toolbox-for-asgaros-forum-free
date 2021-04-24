<?php


namespace Tfaf\Inc\Api;

class SettingsApi
{
    public $admin_pages = array();
    public $admin_subpages = array();
    public $settings = array();
    public $sections = array();
    public $fields = array();


    public function register()
    {
        if (!empty($this->admin_pages) || !empty($this->admin_subpages)) {
            add_action('admin_menu', array($this, 'addAdminMenu'));
        }

        if (!empty($this->settings)) {
            add_action('admin_init', array($this, 'registerCustomFields'));
        }
    }

    /**
     * Add pages to the $admin_pages array
     *
     * @param array $pages pages to add
     *
     * @return $this
     */
    public function AddPages(array $pages)
    {
        $this->admin_pages = $pages;

        return $this;
    }

    /**
     * Create a subpage for an admin page with a given title and store it in the $admin_subpages array
     *
     * @param string|null $title Menu title for the subpage
     *
     * @return $this
     */
    public function withSubPage($title = null)
    {
        if (empty($this->admin_pages)) {
            return $this;
        }

        $admin_page = $this->admin_pages[0];

        $subpage = array(
            array(
                'parent_slug' => $admin_page['menu_slug'],
                'page_title' => $admin_page['page_title'],
                'menu_title' => ($title) ? $title : $admin_page['menu_title'],
                'capability' => $admin_page['capability'],
                'menu_slug' => $admin_page['menu_slug'],
                'callback' => $admin_page['callback']
            )
        );

        $this->admin_subpages = $subpage;

        return $this;
    }

    /**
     * Add subpages to the $admin_subpages array
     *
     * @param array $pages subpages to add
     *
     * @return $this
     */
    public function addSubPages(array $pages)
    {
        $this->admin_subpages = array_merge($this->admin_subpages, $pages);

        return $this;
    }

    /**
     * Create a menu entry for all the admin pages and subpages from $admin_pages and $admin_subpages     *
     */
    public function addAdminMenu()
    {
        foreach ($this->admin_pages as $page) {
            add_menu_page($page['page_title'], $page['menu_title'], $page['capability'], $page['menu_slug'],
                $page['callback'], $page['icon_url'], $page['position']);
        }

        foreach ($this->admin_subpages as $page) {
            add_submenu_page($page['parent_slug'], $page['page_title'], $page['menu_title'], $page['capability'],
                $page['menu_slug'], $page['callback']);
        }
    }

    /**
     * Register all settings, sections and settings_fields
     */
    public function registerCustomFields()
    {
        // register setting
        foreach ($this->settings as $setting) {
            register_setting($setting["option_group"], $setting["option_name"], isset($setting["callback"]) ?
                $setting["callback"] : '');
        }

        // add settings section
        foreach ($this->sections as $section) {
            add_settings_section($section["id"], $section["title"], isset($section["callback"]) ?
                $section["callback"] : '', $section["page"]);
        }

        // add settings field
        foreach ($this->fields as $field) {
            add_settings_field($field["id"], $field["title"], isset($field["callback"]) ? $field["callback"] : '',
                $field["page"], $field["section"], isset($field["args"]) ? $field["args"] : '');
        }
    }

    /**
     * Set all Settings to register
     *
     * @param array $settings
     *
     * @return $this
     */
    public function setSettings(array $settings)
    {
        $this->settings = $settings;

        return $this;
    }

    /**
     * Set all Sections to register
     *
     * @param array $sections
     *
     * @return $this
     */
    public function setSections(array $sections)
    {
        $this->sections = $sections;

        return $this;
    }

    /**
     * Add Sections to register
     *
     * @param array $sections
     *
     * @return $this
     */
    public function addSections(array $sections)
    {
        array_push($this->sections,  $sections);

        return $this;
    }


    /**
     * Set all Settings_Fields to register
     *
     * @param array $fields
     *
     * @return $this
     */
    public function setFields(array $fields)
    {
        $this->fields = $fields;

        return $this;
    }

    /**
     * Add Settings_Fields to register
     *
     * @param array $fields
     *
     * @return $this
     */
    public function addFields($fields)
    {
        foreach ($fields as $field) {

            array_push($this->fields,  $field);

        }

        return $this;
    }
}
