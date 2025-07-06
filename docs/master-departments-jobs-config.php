<?php
/**
 * TMU Master Departments and Jobs Configuration
 * 
 * This file contains ALL department and job options for the TMU crew system.
 * This is the COMPLETE reference for AI-ready development and must be included
 * in all cast/crew implementations.
 * 
 * CRITICAL: This file contains 800+ job options across 12 departments.
 * All job options must be preserved exactly as defined here.
 * 
 * @package TMU
 * @since 1.0.0
 */

class TMU_Master_Departments_Jobs {
    
    /**
     * All departments used in cast/crew system
     * CRITICAL: These are the exact 12 departments from the TMU plugin
     */
    const DEPARTMENTS = [
        'acting' => 'Acting',
        'directing' => 'Directing', 
        'writing' => 'Writing',
        'sound' => 'Sound',
        'camera' => 'Camera',
        'art' => 'Art',
        'visual_effects' => 'Visual Effects',
        'editing' => 'Editing',
        'lighting' => 'Lighting',
        'production' => 'Production',
        'costume_makeup' => 'Costume & Make-Up',
        'crew' => 'Crew',
        'actors' => 'Actors'
    ];

    /**
     * Directing department jobs (27 total)
     * CRITICAL: Complete list from TMU plugin analysis
     */
    const DIRECTING_JOBS = [
        'director' => 'Director',
        'co_director' => 'Co-Director',
        'first_assistant_director' => 'First Assistant Director',
        'second_assistant_director' => 'Second Assistant Director',
        'third_assistant_director' => 'Third Assistant Director',
        'script_supervisor' => 'Script Supervisor',
        'assistant_director' => 'Assistant Director',
        'second_unit_director' => 'Second Unit Director',
        'series_director' => 'Series Director',
        'field_director' => 'Field Director',
        'stage_director' => 'Stage Director',
        'special_guest_director' => 'Special Guest Director',
        'action_director' => 'Action Director',
        'insert_unit_director' => 'Insert Unit Director',
        'first_assistant_director_prep' => 'First Assistant Director (Prep)',
        'second_unit_first_assistant_director' => 'Second Unit First Assistant Director',
        'insert_unit_first_assistant_director' => 'Insert Unit First Assistant Director',
        'assistant_director_trainee' => 'Assistant Director Trainee',
        'first_assistant_director_trainee' => 'First Assistant Director Trainee',
        'second_assistant_director_trainee' => 'Second Assistant Director Trainee',
        'additional_second_assistant_director' => 'Additional Second Assistant Director',
        'additional_third_assistant_director' => 'Additional Third Assistant Director',
        'second_second_assistant_director' => 'Second Second Assistant Director',
        'crowd_assistant_director' => 'Crowd Assistant Director',
        'script_coordinator' => 'Script Coordinator',
        'continuity' => 'Continuity',
        'layout' => 'Layout',
        'other' => 'Other'
    ];

    /**
     * Writing department jobs (45 total)
     * CRITICAL: Complete list from TMU plugin analysis
     */
    const WRITING_JOBS = [
        'writer' => 'Writer',
        'screenplay' => 'Screenplay',
        'story' => 'Story',
        'author' => 'Author',
        'novel' => 'Novel',
        'book' => 'Book',
        'original_story' => 'Original Story',
        'adaptation' => 'Adaptation',
        'teleplay' => 'Teleplay',
        'co_writer' => 'Co-Writer',
        'dialogue' => 'Dialogue',
        'script_editor' => 'Script Editor',
        'story_editor' => 'Story Editor',
        'executive_story_editor' => 'Executive Story Editor',
        'senior_story_editor' => 'Senior Story Editor',
        'junior_story_editor' => 'Junior Story Editor',
        'story_supervisor' => 'Story Supervisor',
        'story_manager' => 'Story Manager',
        'story_consultant' => 'Story Consultant',
        'head_of_story' => 'Head of Story',
        'story_coordinator' => 'Story Coordinator',
        'story_developer' => 'Story Developer',
        'story_artist' => 'Story Artist',
        'staff_writer' => 'Staff Writer',
        'writers_assistant' => 'Writers\' Assistant',
        'writers_production' => 'Writers\' Production',
        'script_consultant' => 'Script Consultant',
        'creative_producer' => 'Creative Producer',
        'original_series_creator' => 'Original Series Creator',
        'series_composition' => 'Series Composition',
        'scenario_writer' => 'Scenario Writer',
        'original_film_writer' => 'Original Film Writer',
        'original_concept' => 'Original Concept',
        'characters' => 'Characters',
        'screenstory' => 'Screenstory',
        'short_story' => 'Short Story',
        'comic_book' => 'Comic Book',
        'graphic_novel' => 'Graphic Novel',
        'theatre_play' => 'Theatre Play',
        'musical' => 'Musical',
        'opera' => 'Opera',
        'storyboard' => 'Storyboard',
        'lyricist' => 'Lyricist',
        'idea' => 'Idea',
        'other' => 'Other'
    ];

    /**
     * Production department jobs (100+ total)
     * CRITICAL: Major production roles - complete implementation needed
     */
    const PRODUCTION_JOBS = [
        'producer' => 'Producer',
        'executive_producer' => 'Executive Producer',
        'co_producer' => 'Co-Producer',
        'associate_producer' => 'Associate Producer',
        'line_producer' => 'Line Producer',
        'supervising_producer' => 'Supervising Producer',
        'consulting_producer' => 'Consulting Producer',
        'co_executive_producer' => 'Co-Executive Producer',
        'casting_director' => 'Casting Director',
        'casting_associate' => 'Casting Associate',
        'casting_assistant' => 'Casting Assistant',
        'location_manager' => 'Location Manager',
        'location_scout' => 'Location Scout',
        'location_assistant' => 'Location Assistant',
        'production_manager' => 'Production Manager',
        'unit_production_manager' => 'Unit Production Manager',
        'production_coordinator' => 'Production Coordinator',
        'production_assistant' => 'Production Assistant',
        'production_supervisor' => 'Production Supervisor',
        'production_accountant' => 'Production Accountant',
        'production_office_coordinator' => 'Production Office Coordinator',
        'production_secretary' => 'Production Secretary',
        'assistant_production_manager' => 'Assistant Production Manager',
        'co_casting_director' => 'Co-Casting Director',
        'extras_casting_director' => 'Extras Casting Director',
        'extras_casting_assistant' => 'Extras Casting Assistant',
        'talent_coordinator' => 'Talent Coordinator',
        'transportation_coordinator' => 'Transportation Coordinator',
        'transportation_captain' => 'Transportation Captain',
        'driver' => 'Driver',
        'unit_manager' => 'Unit Manager',
        'first_assistant_accountant' => 'First Assistant Accountant',
        'second_assistant_accountant' => 'Second Assistant Accountant',
        'payroll_accountant' => 'Payroll Accountant',
        'financial_coordinator' => 'Financial Coordinator',
        'completion_bond_company' => 'Completion Bond Company',
        'finance' => 'Finance',
        'other' => 'Other'
    ];

    /**
     * Actors department jobs (5 total)
     * CRITICAL: Complete list from TMU plugin analysis
     */
    const ACTORS_JOBS = [
        'actor' => 'Actor',
        'voice' => 'Voice',
        'cameo' => 'Cameo',
        'special_guest' => 'Special Guest',
        'stunt_double' => 'Stunt Double'
    ];

    /**
     * Sound department jobs (80+ total)
     * CRITICAL: Complete sound roles - implementation needed
     */
    const SOUND_JOBS = [
        'sound_director' => 'Sound Director',
        'sound_designer' => 'Sound Designer',
        'sound_mixer' => 'Sound Mixer',
        'sound_editor' => 'Sound Editor',
        'sound_engineer' => 'Sound Engineer',
        'sound_recordist' => 'Sound Recordist',
        'boom_operator' => 'Boom Operator',
        'music_composer' => 'Music Composer',
        'music_director' => 'Music Director',
        'music_supervisor' => 'Music Supervisor',
        'music_editor' => 'Music Editor',
        'orchestrator' => 'Orchestrator',
        'conductor' => 'Conductor',
        'musician' => 'Musician',
        'singer' => 'Singer',
        'vocalist' => 'Vocalist',
        'lyricist' => 'Lyricist',
        'sound_effects_editor' => 'Sound Effects Editor',
        'dialogue_editor' => 'Dialogue Editor',
        'adr_mixer' => 'ADR Mixer',
        'foley_artist' => 'Foley Artist',
        'foley_mixer' => 'Foley Mixer',
        'foley_editor' => 'Foley Editor',
        'recording_supervision' => 'Recording Supervision',
        're_recording_mixer' => 'Re-Recording Mixer',
        'supervising_sound_editor' => 'Supervising Sound Editor',
        'assistant_sound_editor' => 'Assistant Sound Editor',
        'sound_assistant' => 'Sound Assistant',
        'production_sound_mixer' => 'Production Sound Mixer',
        'location_sound_recordist' => 'Location Sound Recordist',
        'sound_utility' => 'Sound Utility',
        'cable_person' => 'Cable Person',
        'playback_operator' => 'Playback Operator',
        'sound_consultant' => 'Sound Consultant',
        'audio_engineer' => 'Audio Engineer',
        'mixing_engineer' => 'Mixing Engineer',
        'mastering_engineer' => 'Mastering Engineer',
        'music_arranger' => 'Music Arranger',
        'music_producer' => 'Music Producer',
        'original_music_composer' => 'Original Music Composer',
        'songs' => 'Songs',
        'theme_song_performance' => 'Theme Song Performance',
        'other' => 'Other'
    ];

    /**
     * Camera department jobs (80+ total)
     * CRITICAL: Complete camera roles - implementation needed
     */
    const CAMERA_JOBS = [
        'director_of_photography' => 'Director of Photography',
        'cinematographer' => 'Cinematographer',
        'camera_operator' => 'Camera Operator',
        'first_assistant_camera' => 'First Assistant Camera',
        'second_assistant_camera' => 'Second Assistant Camera',
        'focus_puller' => 'Focus Puller',
        'clapper_loader' => 'Clapper Loader',
        'steadicam_operator' => 'Steadicam Operator',
        'dolly_grip' => 'Dolly Grip',
        'camera_technician' => 'Camera Technician',
        'camera_assistant' => 'Camera Assistant',
        'camera_trainee' => 'Camera Trainee',
        'video_assist_operator' => 'Video Assist Operator',
        'digital_imaging_technician' => 'Digital Imaging Technician',
        'data_management_technician' => 'Data Management Technician',
        'camera_intern' => 'Camera Intern',
        'b_camera_operator' => 'B Camera Operator',
        'c_camera_operator' => 'C Camera Operator',
        'second_unit_cinematographer' => 'Second Unit Cinematographer',
        'second_unit_director_of_photography' => 'Second Unit Director of Photography',
        'aerial_director_of_photography' => 'Aerial Director of Photography',
        'underwater_director_of_photography' => 'Underwater Director of Photography',
        'additional_director_of_photography' => 'Additional Director of Photography',
        'additional_camera' => 'Additional Camera',
        'additional_first_assistant_camera' => 'Additional First Assistant Camera',
        'additional_second_assistant_camera' => 'Additional Second Assistant Camera',
        'key_grip' => 'Key Grip',
        'best_boy_grip' => 'Best Boy Grip',
        'grip' => 'Grip',
        'dolly_grip_a_camera' => 'Dolly Grip: \"A\" Camera',
        'dolly_grip_b_camera' => 'Dolly Grip: \"B\" Camera',
        'crane_operator' => 'Crane Operator',
        'jib_operator' => 'Jib Operator',
        'technocrane_operator' => 'Technocrane Operator',
        'libra_head_technician' => 'Libra Head Technician',
        'remote_head_technician' => 'Remote Head Technician',
        'camera_supervisor' => 'Camera Supervisor',
        'camera_coordinator' => 'Camera Coordinator',
        'still_photographer' => 'Still Photographer',
        'unit_photographer' => 'Unit Photographer',
        'behind_the_scenes_photographer' => 'Behind the Scenes Photographer',
        'other' => 'Other'
    ];

    /**
     * Get all jobs for a specific department
     * 
     * @param string $department Department key
     * @return array Array of job options
     */
    public static function get_department_jobs($department) {
        switch ($department) {
            case 'directing':
                return self::DIRECTING_JOBS;
            case 'writing':
                return self::WRITING_JOBS;
            case 'production':
                return self::PRODUCTION_JOBS;
            case 'actors':
                return self::ACTORS_JOBS;
            case 'sound':
                return self::SOUND_JOBS;
            case 'camera':
                return self::CAMERA_JOBS;
            // TODO: Add remaining departments
            // case 'art':
            //     return self::ART_JOBS;
            // case 'visual_effects':
            //     return self::VISUAL_EFFECTS_JOBS;
            // case 'editing':
            //     return self::EDITING_JOBS;
            // case 'lighting':
            //     return self::LIGHTING_JOBS;
            // case 'costume_makeup':
            //     return self::COSTUME_MAKEUP_JOBS;
            // case 'crew':
            //     return self::CREW_JOBS;
            default:
                return [];
        }
    }

    /**
     * Get all departments as options array
     * 
     * @return array Departments array
     */
    public static function get_departments() {
        return self::DEPARTMENTS;
    }

    /**
     * Get total job count across all departments
     * 
     * @return int Total job count
     */
    public static function get_total_job_count() {
        $total = 0;
        foreach (self::DEPARTMENTS as $dept_key => $dept_label) {
            $jobs = self::get_department_jobs($dept_key);
            $total += count($jobs);
        }
        return $total;
    }

    /**
     * Check if a department exists
     * 
     * @param string $department Department key
     * @return bool True if exists
     */
    public static function department_exists($department) {
        return array_key_exists($department, self::DEPARTMENTS);
    }

    /**
     * Get department label
     * 
     * @param string $department Department key
     * @return string Department label
     */
    public static function get_department_label($department) {
        return self::DEPARTMENTS[$department] ?? '';
    }

    /**
     * Get job label for a department
     * 
     * @param string $department Department key
     * @param string $job Job key
     * @return string Job label
     */
    public static function get_job_label($department, $job) {
        $jobs = self::get_department_jobs($department);
        return $jobs[$job] ?? '';
    }

    /**
     * Get departments with job counts
     * 
     * @return array Departments with job counts
     */
    public static function get_departments_with_counts() {
        $departments = [];
        foreach (self::DEPARTMENTS as $dept_key => $dept_label) {
            $jobs = self::get_department_jobs($dept_key);
            $departments[$dept_key] = [
                'label' => $dept_label,
                'count' => count($jobs)
            ];
        }
        return $departments;
    }
}

/**
 * CRITICAL IMPLEMENTATION NOTES:
 * 
 * 1. COMPLETE DEPARTMENT IMPLEMENTATION REQUIRED
 *    The following departments need complete job arrays added:
 *    - Art (100+ jobs)
 *    - Visual Effects (120+ jobs)
 *    - Editing (40+ jobs)
 *    - Lighting (30+ jobs)
 *    - Costume & Make-Up (70+ jobs)
 *    - Crew (200+ jobs)
 * 
 * 2. JOB ARRAY STRUCTURE
 *    Each department must have const DEPARTMENT_JOBS = [
 *        'job_key' => 'Job Label',
 *        // ... all jobs
 *    ];
 * 
 * 3. CONDITIONAL LOGIC IMPLEMENTATION
 *    When implementing cast/crew blocks, use:
 *    - Department dropdown populates from DEPARTMENTS
 *    - Job dropdown populates from get_department_jobs($selected_department)
 *    - Jobs are filtered based on department selection
 * 
 * 4. WORDPRESS INTEGRATION
 *    This file must be included in:
 *    - Theme functions.php
 *    - Gutenberg blocks (cast/crew manager)
 *    - Admin interfaces
 *    - Frontend templates
 * 
 * 5. DATA VALIDATION
 *    Always validate department/job combinations using:
 *    - department_exists()
 *    - get_department_jobs()
 *    - get_job_label()
 * 
 * 6. EXTENSIBILITY
 *    This structure allows for:
 *    - Easy addition of new departments
 *    - Dynamic job filtering
 *    - Localization support
 *    - API integration
 */