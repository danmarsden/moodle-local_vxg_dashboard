<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

use local_vxg_dashboard\event\vxg_dashboard_viewed;

require_once('../../config.php');
require_once(__DIR__ . '/locallib.php');

$id = optional_param('id', 0, PARAM_INT);

$edit  = optional_param('edit', null, PARAM_BOOL); // Turn editing on and off.
$reset = optional_param('reset', null, PARAM_BOOL);

require_login();

if ($id == 0) {
    redirect(new moodle_url('/my'));
}

$dashboardsettings = $DB->get_record('local_vxg_dashboard', array('id' => $id));

if ($dashboardsettings->dashboard_name == null && $dashboardsettings->dashboard_name == '') {
    $dashboard = get_string('dashboard', 'local_vxg_dashboard');
} else {
    $dashboard = $dashboardsettings->dashboard_name;
}

$userid    = $USER->id;
$context   = context_system::instance();
$header    = $dashboard;
$pagetitle = $dashboard;

// Start setting up the page.
$params = array('id' => $id);
$PAGE->set_context($context);
$PAGE->set_url('/local/vxg_dashboard/index.php', $params);
$PAGE->set_pagelayout('mydashboard');
$PAGE->set_pagetype('veloxnet-dashboard-' . $dashboardsettings->id);
$PAGE->blocks->add_region('content');
$PAGE->set_title($pagetitle);
$PAGE->set_heading($header);
$PAGE->requires->css(new \moodle_url('/local/vxg_dashboard/styles.css'));

// Toggle the editing state and switches.
if ($PAGE->user_allowed_editing()) {
    if ($edit !== null) { // Editing state was specified.
        $USER->editing = $edit; // Change editing state.
    }
    // Add button for editing page.
    $params = array('edit' => !$edit);

    $resetbutton = '';
    $resetstring = get_string('resetpage', 'my');
    $reseturl    = new moodle_url("/local/vxg_dashboard/index.php", array('id' => $id, 'edit' => 1, 'reset' => 1));

    if (has_capability('local/vxg_dashboard:managedashboard', $context)) {

        if (!isset($USER->editing) || !$USER->editing) {
            $editstring  = get_string('updatemymoodleon');
            $resetbutton = $OUTPUT->single_button($reseturl, $resetstring);
        } else {
            $editstring  = get_string('updatemymoodleoff');
            $resetbutton = $OUTPUT->single_button($reseturl, $resetstring);
        }

        $params['id'] = $id;
        $editurl      = new moodle_url("/local/vxg_dashboard/index.php", $params);
        $editbutton   = $OUTPUT->single_button($editurl, $editstring);

        $returnurl    = new moodle_url('/local/vxg_dashboard/index.php', array('id' => $id));
        $manageurl    = new moodle_url("/local/vxg_dashboard/manage.php", array('returnurl' => $returnurl));
        $managebutton = $OUTPUT->single_button($manageurl, get_string('manage', 'local_vxg_dashboard'));
        $PAGE->set_button($managebutton . $editbutton);
    }
} else {
    $USER->editing = $edit = 0;
}

if ($dashboardsettings->layout != 'classic') {
    $PAGE->blocks->set_default_region('content');
}

echo $OUTPUT->header();
if (!empty($dashboardsettings->layout)) {
    if ($dashboardsettings->layout == 'col2') {
        echo html_writer::tag('div', $OUTPUT->custom_block_region('content'), array('class' => 'two-block-columns'));
    } else if ($dashboardsettings->layout == 'col3') {
        echo html_writer::tag('div', $OUTPUT->custom_block_region('content'), array('class' => 'three-block-columns'));
    } else if ($dashboardsettings->layout == 'colmore') {
        echo html_writer::tag('div', $OUTPUT->custom_block_region('content'), array('class' => 'auto-fit-block-columns'));
    } else {
        echo $OUTPUT->custom_block_region('content');
    }
} else {
    echo $OUTPUT->custom_block_region('content');
}

// Trigger event, vxg dashboard viewed.
$eventparams = array('context' => $PAGE->context, 'objectid' => $id);
$event = vxg_dashboard_viewed::create($eventparams);
$event->trigger();

echo $OUTPUT->footer();
