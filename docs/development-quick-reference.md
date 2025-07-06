# TMU Development Quick Reference

## 🎯 Essential Files

### **Master Configuration**
- **`docs/master-departments-jobs-config.php`** - Complete department/job definitions (800+ jobs)
- **`docs/tmu-departments-jobs-config.php`** - Basic configuration file
- **`docs/ai-ready-development-guide.md`** - Comprehensive development guide

### **Analysis Documents**
- **`docs/tmu-plugin-field-analysis.md`** - Complete field structure analysis
- **`docs/tmu-field-implementation-summary.md`** - Implementation summary

### **Implementation Guides**
- **`docs/step-07-gutenberg-block-system.md`** - Gutenberg blocks implementation
- **`docs/README.md`** - Main project documentation

## 🏗️ Department & Job System

### **12 Departments Total**
```php
const DEPARTMENTS = [
    'acting' => 'Acting',
    'directing' => 'Directing',        // 27 jobs ✅
    'writing' => 'Writing',           // 45 jobs ✅
    'sound' => 'Sound',               // 80+ jobs 🔄
    'camera' => 'Camera',             // 80+ jobs 🔄
    'art' => 'Art',                   // 100+ jobs ❌
    'visual_effects' => 'Visual Effects', // 120+ jobs ❌
    'editing' => 'Editing',           // 40+ jobs ❌
    'lighting' => 'Lighting',         // 30+ jobs ❌
    'production' => 'Production',     // 100+ jobs 🔄
    'costume_makeup' => 'Costume & Make-Up', // 70+ jobs ❌
    'crew' => 'Crew',                 // 200+ jobs ❌
    'actors' => 'Actors'              // 5 jobs ✅
];
```

### **Implementation Status**
- ✅ **Complete**: Directing (27), Writing (45), Actors (5)
- 🔄 **Partial**: Production (38), Sound (43), Camera (42)
- ❌ **Missing**: Art, Visual Effects, Editing, Lighting, Costume & Make-Up, Crew

### **Critical Usage**
```php
// Get department jobs
$jobs = TMU_Master_Departments_Jobs::get_department_jobs('directing');

// Validate department/job
$valid = TMU_Master_Departments_Jobs::department_exists('directing');
$job_valid = array_key_exists('director', $jobs);

// Get labels
$dept_label = TMU_Master_Departments_Jobs::get_department_label('directing');
$job_label = TMU_Master_Departments_Jobs::get_job_label('directing', 'director');
```

## 🔧 Development Patterns

### **Cast/Crew Block Structure**
```php
// Register block
register_block_type('tmu/cast-crew-manager', [
    'attributes' => [
        'cast_members' => ['type' => 'array'],
        'crew_members' => ['type' => 'array']
    ],
    'render_callback' => 'render_cast_crew_manager_block'
]);
```

### **Data Structure**
```php
// Cast/Crew data format
$cast_crew_data = [
    'cast' => [
        [
            'person_id' => 123,
            'character' => 'Character Name',
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

### **Conditional Job Selection (JavaScript)**
```javascript
// React component for job selection
function JobSelector({ department, selectedJob, onChange }) {
    const jobs = departmentJobs[department] || [];
    
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

## 📊 Database Schema

### **Custom Tables**
```sql
-- Main content tables
tmu_movies
tmu_tv_series
tmu_tv_series_seasons
tmu_tv_series_episodes
tmu_people
tmu_dramas

-- Relationship table
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

## 🎨 Frontend Templates

### **Display Cast/Crew**
```php
function display_cast_crew($post_id) {
    $cast = get_post_cast($post_id);
    $crew = get_post_crew($post_id);
    
    // Display cast
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
    
    // Display crew by department
    $crew_by_department = group_crew_by_department($crew);
    foreach ($crew_by_department as $department => $crew_members) {
        echo '<h4>' . TMU_Master_Departments_Jobs::get_department_label($department) . '</h4>';
        foreach ($crew_members as $crew_member) {
            echo '<div class="crew-member">';
            echo '<a href="' . get_permalink($crew_member['person_id']) . '">';
            echo get_the_title($crew_member['person_id']);
            echo '</a>';
            echo ' - ' . TMU_Master_Departments_Jobs::get_job_label($department, $crew_member['job']);
            echo '</div>';
        }
    }
}
```

## 🚨 Critical Implementation Notes

### **1. Missing Job Arrays**
**URGENT**: The following departments need complete job arrays in `master-departments-jobs-config.php`:
- Art Department (100+ jobs)
- Visual Effects (120+ jobs)
- Editing (40+ jobs)
- Lighting (30+ jobs)
- Costume & Make-Up (70+ jobs)
- Crew (200+ jobs)

### **2. Validation Requirements**
```php
// Always validate before saving
function validate_cast_crew_data($data) {
    foreach ($data['cast'] as $cast_member) {
        if (!validate_department_job($cast_member['department'], $cast_member['job'])) {
            return false;
        }
    }
    return true;
}
```

### **3. Required WordPress Integration**
- **Files**: `includes/config/departments-jobs.php`
- **Blocks**: `includes/blocks/cast-crew-manager/`
- **Relationships**: `includes/relationships/`
- **Admin**: `assets/src/js/admin/`

## 🔄 TMDB Integration

### **Cast/Crew Import**
```php
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

## 📱 Content Types

### **Post Types with Cast/Crew**
- **Movies** (`movie`) - Full cast/crew system
- **TV Series** (`tv`) - Full cast/crew system
- **TV Episodes** (`episode`) - Full cast/crew system
- **Dramas** (`drama`) - Full cast/crew system

### **People Post Type**
- **People** (`people`) - Biography, filmography, relationships
- **Relationships**: Known for, spouse, family
- **Social Media**: Facebook, Instagram, YouTube, X

## 🎛️ Admin Interface

### **Cast/Crew Manager**
- Department dropdown (12 options)
- Job dropdown (conditional based on department)
- Person selection (AJAX search)
- Character name (for cast)
- Credit order
- Bulk editing capabilities

### **Person Selection**
- AJAX-powered search
- Create new person option
- Relationship management
- Filmography display

## 🔍 Search & Filter

### **Search by Cast/Crew**
```php
// Search movies by cast/crew
function search_by_cast_crew($person_id, $department = null, $job = null) {
    $query = new WP_Query([
        'post_type' => ['movie', 'tv', 'drama'],
        'meta_query' => [
            [
                'key' => 'cast_crew_person_id',
                'value' => $person_id,
                'compare' => 'LIKE'
            ]
        ]
    ]);
    
    return $query;
}
```

## 🎯 Testing Checklist

### **Functional Testing**
- [ ] All 12 departments available
- [ ] Conditional job dropdowns working
- [ ] Person selection and creation
- [ ] Cast/crew relationships saving
- [ ] Frontend display working
- [ ] TMDB import working

### **Data Validation**
- [ ] Department/job validation
- [ ] Person ID validation
- [ ] Character name sanitization
- [ ] Credit order handling
- [ ] Bulk operations

### **Performance Testing**
- [ ] Large cast/crew lists
- [ ] Person search performance
- [ ] Database query optimization
- [ ] Frontend rendering speed

## 📚 Complete Documentation

### **Read These Files**
1. `docs/ai-ready-development-guide.md` - Complete development guide
2. `docs/master-departments-jobs-config.php` - All department/job definitions
3. `docs/tmu-plugin-field-analysis.md` - Complete field analysis
4. `docs/step-07-gutenberg-block-system.md` - Gutenberg implementation

### **Implementation Order**
1. Complete missing job arrays in master config
2. Implement cast/crew Gutenberg blocks
3. Add person selection interface
4. Create database tables
5. Implement frontend templates
6. Add TMDB integration
7. Test all functionality

---

**This quick reference provides all essential information needed for TMU cast/crew system development. Refer to the full documentation for detailed implementation guidance.**