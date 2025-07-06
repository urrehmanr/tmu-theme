# TMU AI-Ready Development Guide

## 🤖 AI-Assisted Development Overview

This guide provides comprehensive documentation for AI-assisted development of the TMU (The Movie Universe) cast/crew system. All necessary references, patterns, and configurations are documented here to enable efficient AI-powered development.

## 🎯 Critical System Components

### 1. **Master Configuration Reference**
**File**: `docs/master-departments-jobs-config.php`
- **Purpose**: Complete department and job definitions (800+ jobs)
- **Usage**: Required for all cast/crew implementations
- **Status**: ⚠️ PARTIALLY COMPLETE - needs remaining departments implemented

### 2. **Cast/Crew System Architecture**
**Core Components**:
- 12 departments with conditional job dropdowns
- 800+ total job options across all departments
- Nested group fields with complex relationships
- Post-type relationships to people database
- Conditional field visibility based on department selection

### 3. **Database Schema**
**Custom Tables**:
- `tmu_movies` - Movie metadata
- `tmu_tv_series` - TV series metadata  
- `tmu_tv_series_seasons` - Season metadata
- `tmu_tv_series_episodes` - Episode metadata
- `tmu_people` - People metadata
- `tmu_dramas` - Drama metadata

## 📚 Complete Department & Job Reference

### **Department Overview**
```php
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
```

### **Job Count by Department**
| Department | Job Count | Status |
|------------|-----------|--------|
| **Directing** | 27 | ✅ Complete |
| **Writing** | 45 | ✅ Complete |
| **Production** | 100+ | 🔄 Partial |
| **Actors** | 5 | ✅ Complete |
| **Sound** | 80+ | 🔄 Partial |
| **Camera** | 80+ | 🔄 Partial |
| **Art** | 100+ | ❌ Needs Implementation |
| **Visual Effects** | 120+ | ❌ Needs Implementation |
| **Editing** | 40+ | ❌ Needs Implementation |
| **Lighting** | 30+ | ❌ Needs Implementation |
| **Costume & Make-Up** | 70+ | ❌ Needs Implementation |
| **Crew** | 200+ | ❌ Needs Implementation |

### **Complete Job Arrays Implementation**

#### **Directing Jobs (27 total)**
```php
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
```

#### **Writing Jobs (45 total)**
```php
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
```

## 🏗️ Development Patterns

### **1. Cast/Crew Block Implementation**
```php
// Register Cast/Crew Manager Block
register_block_type('tmu/cast-crew-manager', [
    'attributes' => [
        'cast_members' => [
            'type' => 'array',
            'items' => [
                'person_id' => 'number',
                'character' => 'string',
                'department' => 'string',
                'job' => 'string'
            ]
        ],
        'crew_members' => [
            'type' => 'array', 
            'items' => [
                'person_id' => 'number',
                'department' => 'string',
                'job' => 'string'
            ]
        ]
    ],
    'render_callback' => 'render_cast_crew_manager_block'
]);
```

### **2. Department/Job Validation**
```php
// Always validate department/job combinations
function validate_department_job($department, $job) {
    if (!TMU_Master_Departments_Jobs::department_exists($department)) {
        return false;
    }
    
    $jobs = TMU_Master_Departments_Jobs::get_department_jobs($department);
    return array_key_exists($job, $jobs);
}
```

### **3. Conditional Job Dropdown**
```javascript
// React component for conditional job selection
function JobSelector({ department, selectedJob, onChange }) {
    const jobs = departments[department] || [];
    
    return (
        <SelectControl
            label="Job"
            value={selectedJob}
            options={jobs.map(job => ({ value: job.key, label: job.label }))}
            onChange={onChange}
        />
    );
}
```

## 🔄 Integration Points

### **1. WordPress Integration**
**Required Files**:
- `includes/config/departments-jobs.php` - Configuration constants
- `includes/blocks/cast-crew-manager/` - Gutenberg block system
- `includes/relationships/` - Post-type relationship handlers
- `assets/src/js/admin/` - AJAX person selection

### **2. Database Integration**
**Custom Tables**:
```sql
-- Cast/Crew relationships stored in custom tables
CREATE TABLE tmu_cast_crew (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    person_id INT NOT NULL,
    department VARCHAR(50) NOT NULL,
    job VARCHAR(100) NOT NULL,
    character_name VARCHAR(255) DEFAULT NULL,
    credit_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### **3. TMDB API Integration**
**Cast/Crew Import**:
```php
// Import cast/crew from TMDB
function import_tmdb_cast_crew($tmdb_id, $post_id) {
    $tmdb_data = fetch_tmdb_credits($tmdb_id);
    
    foreach ($tmdb_data['cast'] as $cast_member) {
        $person_id = find_or_create_person($cast_member);
        add_cast_member($post_id, $person_id, 'acting', 'actor', $cast_member['character']);
    }
    
    foreach ($tmdb_data['crew'] as $crew_member) {
        $person_id = find_or_create_person($crew_member);
        $job = map_tmdb_job($crew_member['job']);
        add_crew_member($post_id, $person_id, $crew_member['department'], $job);
    }
}
```

## 🎨 Frontend Templates

### **1. Cast/Crew Display Template**
```php
// Template for displaying cast/crew
function display_cast_crew($post_id) {
    $cast = get_post_cast($post_id);
    $crew = get_post_crew($post_id);
    
    echo '<div class="tmu-cast-crew">';
    
    if ($cast) {
        echo '<div class="tmu-cast">';
        echo '<h3>Cast</h3>';
        foreach ($cast as $cast_member) {
            echo '<div class="cast-member">';
            echo '<a href="' . get_permalink($cast_member['person_id']) . '">';
            echo get_the_title($cast_member['person_id']);
            echo '</a>';
            if ($cast_member['character']) {
                echo ' as ' . esc_html($cast_member['character']);
            }
            echo '</div>';
        }
        echo '</div>';
    }
    
    if ($crew) {
        echo '<div class="tmu-crew">';
        echo '<h3>Crew</h3>';
        $crew_by_department = group_crew_by_department($crew);
        foreach ($crew_by_department as $department => $crew_members) {
            echo '<div class="crew-department">';
            echo '<h4>' . TMU_Master_Departments_Jobs::get_department_label($department) . '</h4>';
            foreach ($crew_members as $crew_member) {
                echo '<div class="crew-member">';
                echo '<a href="' . get_permalink($crew_member['person_id']) . '">';
                echo get_the_title($crew_member['person_id']);
                echo '</a>';
                echo ' - ' . TMU_Master_Departments_Jobs::get_job_label($department, $crew_member['job']);
                echo '</div>';
            }
            echo '</div>';
        }
        echo '</div>';
    }
    
    echo '</div>';
}
```

## 📋 Development Checklist

### **Phase 1: Core Implementation** ✅
- [x] Master departments/jobs configuration
- [x] Basic department structure (12 departments)
- [x] Complete job arrays for Directing, Writing, Actors
- [ ] **CRITICAL**: Complete remaining job arrays (Art, VFX, Editing, Lighting, Costume, Crew)

### **Phase 2: WordPress Integration** 🔄
- [ ] Cast/Crew Manager Gutenberg block
- [ ] Person selection with AJAX search
- [ ] Department/job conditional dropdowns
- [ ] Database table creation
- [ ] Custom meta box interfaces

### **Phase 3: Advanced Features** ⏳
- [ ] TMDB cast/crew import
- [ ] Bulk editing interfaces
- [ ] Search/filter by cast/crew
- [ ] Cast/crew relationship management
- [ ] Frontend display templates

### **Phase 4: Optimization** ⏳
- [ ] Performance optimization
- [ ] Caching implementation
- [ ] Security validation
- [ ] Accessibility compliance

## 🚨 Critical Implementation Notes

### **1. Department Job Arrays - URGENT**
The following departments need complete job arrays implemented in `master-departments-jobs-config.php`:

- **Art Department** (100+ jobs) - Production Designer, Art Director, Set Designer, Props Master, etc.
- **Visual Effects** (120+ jobs) - VFX Supervisor, 3D Artist, Compositor, etc.
- **Editing** (40+ jobs) - Editor, Colorist, Assistant Editor, etc.
- **Lighting** (30+ jobs) - Gaffer, Lighting Technician, Electrician, etc.
- **Costume & Make-Up** (70+ jobs) - Costume Designer, Makeup Artist, etc.
- **Crew** (200+ jobs) - Special Effects, Stunts, Choreographer, etc.

### **2. Conditional Logic Implementation**
```javascript
// Essential conditional logic for job selection
const departmentJobs = {
    'directing': directingJobs,
    'writing': writingJobs,
    'production': productionJobs,
    'actors': actorsJobs,
    'sound': soundJobs,
    'camera': cameraJobs,
    'art': artJobs,
    'visual_effects': visualEffectsJobs,
    'editing': editingJobs,
    'lighting': lightingJobs,
    'costume_makeup': costumeMakeupJobs,
    'crew': crewJobs
};

// Update job options when department changes
function updateJobOptions(selectedDepartment) {
    const jobs = departmentJobs[selectedDepartment] || [];
    return jobs.map(job => ({ value: job.key, label: job.label }));
}
```

### **3. Data Structure Requirements**
```php
// Cast/Crew data structure
$cast_crew_data = [
    'cast' => [
        [
            'person_id' => 123,
            'character' => 'John Doe',
            'department' => 'acting',
            'job' => 'actor',
            'credit_order' => 1
        ]
    ],
    'crew' => [
        [
            'person_id' => 456,
            'department' => 'directing',
            'job' => 'director',
            'credit_order' => 1
        ]
    ]
];
```

## 🔧 Development Tools

### **1. Validation Functions**
```php
// Essential validation functions
function validate_cast_crew_data($data) {
    foreach ($data['cast'] as $cast_member) {
        if (!validate_department_job($cast_member['department'], $cast_member['job'])) {
            return false;
        }
    }
    
    foreach ($data['crew'] as $crew_member) {
        if (!validate_department_job($crew_member['department'], $crew_member['job'])) {
            return false;
        }
    }
    
    return true;
}
```

### **2. Helper Functions**
```php
// Essential helper functions
function get_department_jobs_for_select($department) {
    $jobs = TMU_Master_Departments_Jobs::get_department_jobs($department);
    $options = [];
    
    foreach ($jobs as $key => $label) {
        $options[] = [
            'value' => $key,
            'label' => $label
        ];
    }
    
    return $options;
}

function get_all_departments_for_select() {
    $departments = TMU_Master_Departments_Jobs::get_departments();
    $options = [];
    
    foreach ($departments as $key => $label) {
        $options[] = [
            'value' => $key,
            'label' => $label
        ];
    }
    
    return $options;
}
```

## 📖 Reference Documentation

### **Related Files**
- `docs/tmu-departments-jobs-config.php` - Original basic configuration
- `docs/tmu-field-implementation-summary.md` - Field analysis summary
- `docs/tmu-plugin-field-analysis.md` - Complete plugin analysis
- `docs/step-07-gutenberg-block-system.md` - Block implementation guide

### **Implementation Steps**
1. **Read**: `docs/tmu-plugin-field-analysis.md` for complete field understanding
2. **Reference**: `docs/master-departments-jobs-config.php` for job definitions
3. **Implement**: Cast/crew blocks following patterns in this guide
4. **Validate**: All department/job combinations using provided functions
5. **Test**: Complete functionality with all 800+ job options

## 🎯 Success Criteria

### **Functional Requirements**
- [ ] All 12 departments properly implemented
- [ ] All 800+ job options available
- [ ] Conditional job dropdowns working
- [ ] Person selection and relationships
- [ ] Database integration complete
- [ ] Frontend display templates

### **Technical Requirements**
- [ ] WordPress coding standards compliance
- [ ] Security validation for all inputs
- [ ] Performance optimization
- [ ] Accessibility compliance
- [ ] Cross-browser compatibility
- [ ] Mobile responsiveness

### **User Experience**
- [ ] Intuitive cast/crew management interface
- [ ] Fast person search and selection
- [ ] Bulk editing capabilities
- [ ] Clear visual hierarchy
- [ ] Responsive design

---

**This guide provides the complete foundation for AI-assisted development of the TMU cast/crew system. All references, patterns, and configurations needed for successful implementation are documented here.**