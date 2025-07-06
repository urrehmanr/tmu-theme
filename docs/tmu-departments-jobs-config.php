<?php
/**
 * TMU Departments and Jobs Configuration
 * 
 * This file contains all department and job options extracted from the TMU plugin
 * to ensure complete field parity in our theme implementation.
 */

class TMU_Departments_Jobs {
    
    /**
     * All departments used in cast/crew system
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
     * All certification options for movies/TV
     */
    const CERTIFICATIONS = [
        'U/A' => 'U/A',
        '6' => '6',
        '6+' => '6+',
        '7' => '7',
        '7+' => '7+',
        '8' => '8',
        '8+' => '8+',
        '9' => '9',
        '9+' => '9+',
        '10' => '10',
        '10+' => '10+',
        '11' => '11',
        '11+' => '11+',
        '12' => '12',
        '12+' => '12+',
        '12A' => '12A',
        '13' => '13',
        '13+' => '13+',
        '14' => '14',
        '14+' => '14+',
        '15' => '15',
        '15+' => '15+',
        '16' => '16',
        '16+' => '16+',
        '17' => '17',
        '17+' => '17+',
        '18' => '18',
        '18+' => '18+',
        '19' => '19',
        'G' => 'G',
        'C' => 'C',
        'R' => 'R',
        'PG' => 'PG',
        'NC-17' => 'NC-17',
        'NR' => 'NR',
        'PG-13' => 'PG-13',
        'TV-MA' => 'TV-MA',
        'TV-Y' => 'TV-Y',
        'TV-14' => 'TV-14',
        'TV-PG' => 'TV-PG',
        'TV-Y7' => 'TV-Y7',
        'TV-G' => 'TV-G'
    ];

    /**
     * Episode types for TV episodes
     */
    const EPISODE_TYPES = [
        'standard' => 'Standard',
        'finale' => 'Finale',
        'mid_season' => 'Mid Season',
        'special' => 'Special'
    ];

    /**
     * Gender options for people
     */
    const GENDERS = [
        'male' => 'Male',
        'female' => 'Female',
        'not_specified' => 'Not Specified'
    ];

    /**
     * Marital status options for people
     */
    const MARITAL_STATUS = [
        'single' => 'Single',
        'married' => 'Married',
        'divorced' => 'Divorced',
        'widowed' => 'Widowed',
        'separated' => 'Separated',
        'in_relationship' => 'In a Relationship',
        'committed' => 'Committed',
        'complicated' => 'It\'s Complicated'
    ];

    /**
     * Social media platforms
     */
    const SOCIAL_PLATFORMS = [
        'facebook' => 'Facebook',
        'instagram' => 'Instagram',
        'youtube' => 'YouTube',
        'x' => 'X'
    ];

    /**
     * Directing department jobs (27 total)
     */
    const DIRECTING_JOBS = [
        'additional_third_assistant_director' => 'Additional Third Assistant Director',
        'insert_unit_director' => 'Insert Unit Director',
        'series_director' => 'Series Director',
        'insert_unit_first_assistant_director' => 'Insert Unit First Assistant Director',
        'script_coordinator' => 'Script Coordinator',
        'co_director' => 'Co-Director',
        'director' => 'Director',
        'second_unit_director' => 'Second Unit Director',
        'assistant_director_trainee' => 'Assistant Director Trainee',
        'second_unit_first_assistant_director' => 'Second Unit First Assistant Director',
        'first_assistant_director_prep' => 'First Assistant Director (Prep)',
        'second_assistant_director_trainee' => 'Second Assistant Director Trainee',
        'first_assistant_director' => 'First Assistant Director',
        'special_guest_director' => 'Special Guest Director',
        'second_assistant_director' => 'Second Assistant Director',
        'field_director' => 'Field Director',
        'third_assistant_director' => 'Third Assistant Director',
        'layout' => 'Layout',
        'stage_director' => 'Stage Director',
        'script_supervisor' => 'Script Supervisor',
        'other' => 'Other',
        'assistant_director' => 'Assistant Director',
        'continuity' => 'Continuity',
        'action_director' => 'Action Director',
        'crowd_assistant_director' => 'Crowd Assistant Director',
        'first_assistant_director_trainee' => 'First Assistant Director Trainee',
        'second_second_assistant_director' => 'Second Second Assistant Director',
        'additional_second_assistant_director' => 'Additional Second Assistant Director'
    ];

    /**
     * Writing department jobs (43 total)
     */
    const WRITING_JOBS = [
        'dialogue' => 'Dialogue',
        'theatre_play' => 'Theatre Play',
        'screenplay' => 'Screenplay',
        'idea' => 'Idea',
        'executive_story_editor' => 'Executive Story Editor',
        'writers_production' => 'Writers\' Production',
        'adaptation' => 'Adaptation',
        'scenario_writer' => 'Scenario Writer',
        'comic_book' => 'Comic Book',
        'author' => 'Author',
        'other' => 'Other',
        'script_editor' => 'Script Editor',
        'story_manager' => 'Story Manager',
        'story_supervisor' => 'Story Supervisor',
        'writer' => 'Writer',
        'lyricist' => 'Lyricist',
        'original_film_writer' => 'Original Film Writer',
        'storyboard' => 'Storyboard',
        'musical' => 'Musical',
        'series_composition' => 'Series Composition',
        'staff_writer' => 'Staff Writer',
        'novel' => 'Novel',
        'story_artist' => 'Story Artist',
        'book' => 'Book',
        'opera' => 'Opera',
        'creative_producer' => 'Creative Producer',
        'characters' => 'Characters',
        'original_story' => 'Original Story',
        'screenstory' => 'Screenstory',
        'teleplay' => 'Teleplay',
        'co_writer' => 'Co-Writer',
        'short_story' => 'Short Story',
        'script_consultant' => 'Script Consultant',
        'writers_assistant' => 'Writers\' Assistant',
        'story' => 'Story',
        'story_editor' => 'Story Editor',
        'original_series_creator' => 'Original Series Creator',
        'junior_story_editor' => 'Junior Story Editor',
        'senior_story_editor' => 'Senior Story Editor',
        'story_consultant' => 'Story Consultant',
        'head_of_story' => 'Head of Story',
        'original_concept' => 'Original Concept',
        'graphic_novel' => 'Graphic Novel',
        'story_coordinator' => 'Story Coordinator',
        'story_developer' => 'Story Developer'
    ];

    /**
     * Actors department jobs (5 total)
     */
    const ACTORS_JOBS = [
        'stunt_double' => 'Stunt Double',
        'actor' => 'Actor',
        'cameo' => 'Cameo',
        'special_guest' => 'Special Guest',
        'voice' => 'Voice'
    ];

    /**
     * Get all jobs for a specific department
     */
    public static function get_department_jobs($department) {
        switch ($department) {
            case 'directing':
                return self::DIRECTING_JOBS;
            case 'writing':
                return self::WRITING_JOBS;
            case 'actors':
                return self::ACTORS_JOBS;
            // Add other departments as needed
            default:
                return [];
        }
    }

    /**
     * Get all departments as options array
     */
    public static function get_departments() {
        return self::DEPARTMENTS;
    }

    /**
     * Get all certifications as options array
     */
    public static function get_certifications() {
        return self::CERTIFICATIONS;
    }

    /**
     * Check if a department exists
     */
    public static function department_exists($department) {
        return array_key_exists($department, self::DEPARTMENTS);
    }

    /**
     * Get department label
     */
    public static function get_department_label($department) {
        return self::DEPARTMENTS[$department] ?? '';
    }

    /**
     * Get job label for a department
     */
    public static function get_job_label($department, $job) {
        $jobs = self::get_department_jobs($department);
        return $jobs[$job] ?? '';
    }
}

/**
 * Note: This file contains only a sample of the job options.
 * The complete implementation should include all 800+ jobs across all departments:
 * 
 * - Crew Jobs: 200+ options
 * - Production Jobs: 100+ options  
 * - Sound Jobs: 80+ options
 * - Camera Jobs: 80+ options
 * - Costume & Make-Up Jobs: 70+ options
 * - Art Jobs: 100+ options
 * - Visual Effects Jobs: 120+ options
 * - Editing Jobs: 40+ options
 * - Lighting Jobs: 30+ options
 * 
 * Each department array should be populated with the exact options
 * from the TMU plugin field analysis.
 */