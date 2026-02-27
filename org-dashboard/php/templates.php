<?php
// Available document templates
$templates = [
    'meeting_minutes' => [
        'name' => 'Meeting Minutes',
        'fields' => [
            'meeting_date' => 'Meeting Date',
            'meeting_time' => 'Meeting Time',
            'location' => 'Location',
            'attendees' => 'Attendees (comma-separated)',
            'agenda' => 'Agenda',
            'discussion' => 'Discussion Summary',
            'action_items' => 'Action Items',
            'next_meeting' => 'Next Meeting Date'
        ]
    ],
    'event_proposal' => [
        'name' => 'Event Proposal',
        'fields' => [
            'event_name' => 'Event Name',
            'event_date' => 'Proposed Date',
            'event_time' => 'Event Time',
            'location' => 'Location/Venue',
            'objective' => 'Event Objective',
            'target_audience' => 'Target Audience',
            'expected_attendance' => 'Expected Number of Attendees',
            'budget' => 'Estimated Budget',
            'description' => 'Event Description',
            'requirements' => 'Special Requirements'
        ]
    ],
    'financial_report' => [
        'name' => 'Financial Report',
        'fields' => [
            'report_period' => 'Reporting Period',
            'opening_balance' => 'Opening Balance',
            'total_income' => 'Total Income',
            'total_expenses' => 'Total Expenses',
            'expense_breakdown' => 'Expense Breakdown',
            'closing_balance' => 'Closing Balance',
            'remarks' => 'Remarks/Notes'
        ]
    ],
    'incident_report' => [
        'name' => 'Incident Report',
        'fields' => [
            'incident_date' => 'Incident Date',
            'incident_time' => 'Incident Time',
            'location' => 'Location',
            'incident_description' => 'Incident Description',
            'individuals_involved' => 'Individuals Involved',
            'witnesses' => 'Witnesses',
            'action_taken' => 'Action Taken',
            'recommendations' => 'Recommendations'
        ]
    ],
    'membership_form' => [
        'name' => 'Membership Form',
        'fields' => [
            'full_name' => 'Full Name',
            'email' => 'Email Address',
            'phone' => 'Phone Number',
            'course_year' => 'Course and Year',
            'date_joined' => 'Date Joined',
            'membership_role' => 'Membership Role',
            'skills' => 'Skills/Expertise',
            'availability' => 'Availability for Activities'
        ]
    ]
];

function getTemplate($template_id) {
    global $templates;
    return $templates[$template_id] ?? null;
}

function getAllTemplates() {
    global $templates;
    return $templates;
}
?>
