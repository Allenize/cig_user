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
    ],
    'project_proposal' => [
        'name' => 'Project Proposal',
        'fields' => [
            'proposal_date' => 'Date',
            'recipient_1' => 'First Recipient Name & Title',
            'recipient_2' => 'Second Recipient Name & Title',
            'dear_opening' => 'Dear [Recipient - Full Name with Title]',
            'opening_statement' => 'Opening Statement',
            'organization' => 'Organization',
            'project_title' => 'Project Title',
            'project_type' => 'Type of Project (Curricular / Non-Curricular / Off-Campus)',
            'project_involvement' => 'Project Involvement (Host / Collaboration / Participant)',
            'project_location' => 'Project Location',
            'proposed_start_date' => 'Proposed Start Date & Time',
            'proposed_end_date' => 'Proposed Completion Date',
            'number_participants' => 'Number of Participants',
            'project_summary' => 'A. SUMMARY OF THE PROJECT',
            'project_goal' => 'Goal',
            'project_objectives' => 'Objectives (numbered, one per line)',
            'expected_outputs' => 'C. EXPECTED OUTPUTS (bulleted)',
            'budget_source' => 'Source of Fund',
            'budget_partner' => 'Partner/Donation/Subsidy',
            'budget_total' => 'Total Project Cost',
            'monitoring_details' => 'Monitoring (bulleted)',
            'evaluation_details' => 'Evaluation Strategy (bulleted)',
            'security_plan' => 'V. SECURITY PLAN (bulleted)',
            'closing_statement' => 'Closing Statement',
            'sender_name' => 'Sender Name & Title',
            'noted_by' => 'Noted by (comma-separated names with titles)',
            'endorsed_by' => 'Endorsed by (name and title)'
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
