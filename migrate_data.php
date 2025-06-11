<?php
// migrate_data.php
// Assumes old tables are present and new tables (with new schema) are empty and ready for insertion.
// IMPORTANT: Backup your database before running this script!
// If you ran final_schema.sql which drops old tables, you must restore data into
// temporary tables (e.g., old_users, old_services) and update this script to read from them.

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Setup (db connection, functions)
// Ensure this path is correct for your project structure to load DB and functions
if (file_exists(__DIR__ . '/includes/init.php')) {
    require_once __DIR__ . '/includes/init.php';
} elseif (file_exists(dirname(__DIR__) . '/includes/init.php')) {
    // If migrate_data.php is in a subdirectory like 'scripts' or 'migrations'
    require_once dirname(__DIR__) . '/includes/init.php';
} else {
    die("Could not find init.php. Please adjust the path. Ensure this script is run from the project root or includes path is correctly set.");
}


if (!isset($db) || !$db instanceof Database) {
    die("Database connection not established. Check init.php.");
}

echo "Starting data migration...\n\n";
echo "IMPORTANT: This script assumes old tables are readable and new tables (with the new schema) are ready for data insertion.\n";
echo "If you have already run final_schema.sql which DROPS old tables, you must restore your old data into tables with temporary names (e.g., old_users, old_services) and UPDATE THIS SCRIPT to read from those temporary tables before proceeding.\n";
echo "Press Enter to continue if you understand and have prepared the database, or Ctrl+C to cancel...\n";
trim(fgets(STDIN));


// --- Migration for site_settings to settings ---
echo "Migrating site_settings to settings...\n";
try {
    $old_settings_data = $db->queryOne("SELECT * FROM site_settings LIMIT 1"); // Old table had one row
    $migrated_count = 0;
    if ($old_settings_data) {
        $settings_map = [
            // General
            'site_name' => 'site_name',
            'site_tagline' => 'site_tagline',
            'site_description' => 'site_description', // For footer/defaults
            'meta_keywords' => 'meta_keywords', // Global meta keywords
            'contact_email' => 'contact_email',
            'contact_phone' => 'contact_phone',
            'contact_address' => 'contact_address',
            'footer_text' => 'footer_text',
            'google_analytics_id' => 'google_analytics_id',
            'site_logo' => 'site_logo_path', // Path to logo
            'favicon' => 'site_favicon_path', // Path to favicon
            'og_image' => 'og_image_path', // Default OG image

            // Social Links (assuming these were columns in old site_settings)
            'facebook_link' => 'facebook_url',
            'twitter_link' => 'twitter_url',
            'instagram_link' => 'instagram_url',
            'linkedin_link' => 'linkedin_url',
            'youtube_link' => 'youtube_url',
            'whatsapp_link' => 'whatsapp_number', // Or full URL if that's how it was stored

            // Sitemap settings (assuming these were columns)
            'sitemap_auto_generate' => 'sitemap_auto_generate',
            'sitemap_frequency' => 'sitemap_frequency',
            'sitemap_include_images' => 'sitemap_include_images',
            'sitemap_priority_home' => 'sitemap_priority_home',
            'sitemap_priority_services' => 'sitemap_priority_services',
            'sitemap_priority_projects' => 'sitemap_priority_projects',
            'sitemap_last_generated' => 'sitemap_last_generated',

            // Other settings like 'enabled_frontend_sections' if they were columns
             'enabled_frontend_sections' => 'enabled_frontend_sections', // Assuming it was a column with JSON
        ];

        foreach ($settings_map as $old_column => $new_setting_name) {
            if (isset($old_settings_data[$old_column]) && $old_settings_data[$old_column] !== null) {
                $value_to_insert = $old_settings_data[$old_column];
                $db->execute(
                    "INSERT INTO settings (setting_name, setting_value, created_at, updated_at) VALUES (:name, :value, NOW(), NOW())
                     ON DUPLICATE KEY UPDATE setting_value = :value, updated_at = NOW()",
                    [':name' => $new_setting_name, ':value' => $value_to_insert]
                );
                $migrated_count++;
            }
        }
    }
    echo "$migrated_count settings migrated.\n";
} catch (Exception $e) {
    echo "Error migrating site_settings: " . $e->getMessage() . "\n";
}
echo "------------------------------------\n";


// --- Migration for users ---
echo "Migrating users...\n";
try {
    $old_users = $db->query("SELECT user_id, username, password AS password_plain, email, full_name, role, created_at, updated_at FROM users_old"); // Assuming you renamed old table
    // If not renamed, and it was dropped and new one created, this SELECT won't work.
    // This script will assume you've made old data available in 'users_old' or similar.
    // For demonstration, I'll use 'users_old'. Users must adapt this.
    // If inserting into the same 'users' table (after schema change), ensure it's empty.

    // Fallback: if users_old does not exist, try users (less safe if schema not changed yet)
    if (!$old_users && $db->queryOne("SHOW TABLES LIKE 'users'") ) {
         echo "WARNING: 'users_old' table not found. Attempting to read from 'users' table. Ensure new 'users' table is empty if this is not intended.\n";
         $old_users = $db->query("SELECT user_id, username, password AS password_plain, email, full_name, role, created_at, updated_at FROM users");
    }


    $count = 0;
    if ($old_users) {
        foreach ($old_users as $row) {
            $new_user_data = [
                ':id' => $row['user_id'], // Map old user_id to new id if desired, or let new id auto-increment
                ':username' => $row['username'],
                ':password_hash' => password_hash($row['password_plain'], PASSWORD_DEFAULT), // Hash the plain password
                ':email' => $row['email'],
                ':role' => $row['role'] ?? 'viewer', // Default role if not set
                ':created_at' => $row['created_at'] ?? date('Y-m-d H:i:s'),
                ':updated_at' => $row['updated_at'] ?? date('Y-m-d H:i:s')
            ];
            // If new `id` is auto-increment, remove ':id' => $row['user_id'] and 'id' from column list
            // For this example, assuming we try to keep IDs if possible, but this can cause issues if not handled carefully.
            // A safer approach for auto-increment PKs is to not specify the ID in the INSERT.
            $db->execute(
                "INSERT INTO users (id, username, password_hash, email, role, created_at, updated_at)
                 VALUES (:id, :username, :password_hash, :email, :role, :created_at, :updated_at)
                 ON DUPLICATE KEY UPDATE
                 username=VALUES(username), password_hash=VALUES(password_hash), email=VALUES(email), role=VALUES(role), updated_at=VALUES(updated_at)",
                $new_user_data
            );
            $count++;
        }
    }
    echo "$count users migrated.\n";
} catch (Exception $e) {
    echo "Error migrating users: " . $e->getMessage() . "\n";
}
echo "------------------------------------\n";


// --- Migration for services ---
echo "Migrating services...\n";
try {
    $old_services = $db->query("SELECT service_id, title, description AS full_desc, short_description, icon, created_at, updated_at FROM services_old"); // Read from services_old
    // Fallback
    if (!$old_services && $db->queryOne("SHOW TABLES LIKE 'services'") ) {
         echo "WARNING: 'services_old' table not found. Attempting to read from 'services' table.\n";
         $old_services = $db->query("SELECT service_id, title, description AS full_desc, short_description, icon, created_at, updated_at FROM services");
    }

    $count = 0;
    if ($old_services) {
        foreach ($old_services as $row) {
            // Combine descriptions or choose one. Here, using full_desc if available, else short_description.
            $description = !empty($row['full_desc']) ? $row['full_desc'] : $row['short_description'];
            $new_service_data = [
                ':id' => $row['service_id'], // Assuming we try to keep IDs
                ':name' => $row['title'],
                ':description' => $description,
                ':icon_class' => $row['icon'], // Map old 'icon' (Feather) to new 'icon_class' (Font Awesome expected by new design)
                ':created_at' => $row['created_at'] ?? date('Y-m-d H:i:s'),
                ':updated_at' => $row['updated_at'] ?? date('Y-m-d H:i:s')
            ];
            $db->execute(
                "INSERT INTO services (id, name, description, icon_class, created_at, updated_at)
                 VALUES (:id, :name, :description, :icon_class, :created_at, :updated_at)
                 ON DUPLICATE KEY UPDATE name=VALUES(name), description=VALUES(description), icon_class=VALUES(icon_class), updated_at=VALUES(updated_at)",
                $new_service_data
            );
            $count++;
        }
    }
    echo "$count services migrated.\n";
} catch (Exception $e) {
    echo "Error migrating services: " . $e->getMessage() . "\n";
}
echo "------------------------------------\n";

// --- Migration for projects ---
echo "Migrating projects...\n";
try {
    $old_projects = $db->query("SELECT project_id, title, description AS full_desc, short_description, main_image, project_url AS url, created_at, updated_at FROM projects_old");
    if (!$old_projects && $db->queryOne("SHOW TABLES LIKE 'projects'") ) {
         echo "WARNING: 'projects_old' table not found. Attempting to read from 'projects' table.\n";
         $old_projects = $db->query("SELECT project_id, title, description AS full_desc, short_description, main_image, project_url AS url, created_at, updated_at FROM projects");
    }
    $count = 0;
    if ($old_projects) {
        foreach ($old_projects as $row) {
            $description = !empty($row['full_desc']) ? $row['full_desc'] : $row['short_description'];
            $new_project_data = [
                ':id' => $row['project_id'],
                ':title' => $row['title'],
                ':description' => $description,
                ':image_url' => $row['main_image'], // main_image from old table maps to image_url
                ':project_url' => $row['url'],
                ':created_at' => $row['created_at'] ?? date('Y-m-d H:i:s'),
                ':updated_at' => $row['updated_at'] ?? date('Y-m-d H:i:s')
            ];
            $db->execute(
                "INSERT INTO projects (id, title, description, image_url, project_url, created_at, updated_at)
                 VALUES (:id, :title, :description, :image_url, :project_url, :created_at, :updated_at)
                 ON DUPLICATE KEY UPDATE title=VALUES(title), description=VALUES(description), image_url=VALUES(image_url), project_url=VALUES(project_url), updated_at=VALUES(updated_at)",
                $new_project_data
            );
            $count++;
        }
    }
    echo "$count projects migrated.\n";
} catch (Exception $e) {
    echo "Error migrating projects: " . $e->getMessage() . "\n";
}
echo "------------------------------------\n";


// --- Migration for messages ---
echo "Migrating messages...\n";
try {
    $old_messages = $db->query("SELECT id, name, email, subject, message, created_at, is_read FROM messages_old");
     if (!$old_messages && $db->queryOne("SHOW TABLES LIKE 'messages'") ) {
         echo "WARNING: 'messages_old' table not found. Attempting to read from 'messages' table.\n";
         $old_messages = $db->query("SELECT id, name, email, subject, message, created_at, is_read FROM messages");
    }
    $count = 0;
    if ($old_messages) {
        foreach ($old_messages as $row) {
            $new_message_data = [
                ':id' => $row['id'],
                ':sender_name' => $row['name'],
                ':sender_email' => $row['email'],
                ':subject' => $row['subject'],
                ':message_body' => $row['message'],
                ':received_at' => $row['created_at'] ?? date('Y-m-d H:i:s'),
                ':is_read' => $row['is_read'] ?? 0
            ];
            $db->execute(
                "INSERT INTO messages (id, sender_name, sender_email, subject, message_body, received_at, is_read)
                 VALUES (:id, :sender_name, :sender_email, :subject, :message_body, :received_at, :is_read)
                 ON DUPLICATE KEY UPDATE sender_name=VALUES(sender_name), sender_email=VALUES(sender_email), subject=VALUES(subject), message_body=VALUES(message_body), received_at=VALUES(received_at), is_read=VALUES(is_read)",
                $new_message_data
            );
            $count++;
        }
    }
    echo "$count messages migrated.\n";
} catch (Exception $e) {
    echo "Error migrating messages: " . $e->getMessage() . "\n";
}
echo "------------------------------------\n";

// --- Migration for testimonials ---
echo "Migrating testimonials...\n";
try {
    $old_testimonials = $db->query("SELECT testimonial_id, client_name, client_photo, feedback, created_at, updated_at FROM testimonials_old");
    if (!$old_testimonials && $db->queryOne("SHOW TABLES LIKE 'testimonials'") ) {
         echo "WARNING: 'testimonials_old' table not found. Attempting to read from 'testimonials' table.\n";
         $old_testimonials = $db->query("SELECT testimonial_id, client_name, client_photo, feedback, created_at, updated_at FROM testimonials");
    }
    $count = 0;
    if ($old_testimonials) {
        foreach ($old_testimonials as $row) {
            $new_testimonial_data = [
                ':id' => $row['testimonial_id'],
                ':author_name' => $row['client_name'],
                ':testimonial_text' => $row['feedback'],
                ':author_image_url' => $row['client_photo'],
                ':created_at' => $row['created_at'] ?? date('Y-m-d H:i:s'),
                ':updated_at' => $row['updated_at'] ?? date('Y-m-d H:i:s')
            ];
            $db->execute(
                "INSERT INTO testimonials (id, author_name, testimonial_text, author_image_url, created_at, updated_at)
                 VALUES (:id, :author_name, :testimonial_text, :author_image_url, :created_at, :updated_at)
                 ON DUPLICATE KEY UPDATE author_name=VALUES(author_name), testimonial_text=VALUES(testimonial_text), author_image_url=VALUES(author_image_url), updated_at=VALUES(updated_at)",
                $new_testimonial_data
            );
            $count++;
        }
    }
    echo "$count testimonials migrated.\n";
} catch (Exception $e) {
    echo "Error migrating testimonials: " . $e->getMessage() . "\n";
}
echo "------------------------------------\n";


// --- Migration for facts ---
echo "Migrating facts...\n";
try {
    $old_facts = $db->query("SELECT fact_id, title, `number`, icon, created_at, updated_at FROM facts_old"); // `number` is a reserved keyword
    if (!$old_facts && $db->queryOne("SHOW TABLES LIKE 'facts'") ) {
         echo "WARNING: 'facts_old' table not found. Attempting to read from 'facts' table.\n";
         $old_facts = $db->query("SELECT fact_id, title, `number`, icon, created_at, updated_at FROM facts");
    }
    $count = 0;
    if ($old_facts) {
        foreach ($old_facts as $row) {
            $new_fact_data = [
                ':id' => $row['fact_id'],
                ':fact_text' => $row['title'],
                ':fact_value' => $row['number'],
                ':icon_class' => $row['icon'], // Map old 'icon' (Feather) to new 'icon_class' (Font Awesome)
                ':created_at' => $row['created_at'] ?? date('Y-m-d H:i:s'),
                ':updated_at' => $row['updated_at'] ?? date('Y-m-d H:i:s')
            ];
            $db->execute(
                "INSERT INTO facts (id, fact_text, fact_value, icon_class, created_at, updated_at)
                 VALUES (:id, :fact_text, :fact_value, :icon_class, :created_at, :updated_at)
                 ON DUPLICATE KEY UPDATE fact_text=VALUES(fact_text), fact_value=VALUES(fact_value), icon_class=VALUES(icon_class), updated_at=VALUES(updated_at)",
                $new_fact_data
            );
            $count++;
        }
    }
    echo "$count facts migrated.\n";
} catch (Exception $e) {
    echo "Error migrating facts: " . $e->getMessage() . "\n";
}
echo "------------------------------------\n";


// --- Migration for homepage_sections to sections ---
echo "Migrating homepage_sections to sections...\n";
try {
    // Prioritize homepage_sections if it exists, otherwise try sections (old version)
    $old_sections_table_name = $db->queryOne("SHOW TABLES LIKE 'homepage_sections'") ? 'homepage_sections' : 'sections_old';
     if ($old_sections_table_name === 'sections_old' && !$db->queryOne("SHOW TABLES LIKE 'sections_old'")) {
        if ($db->queryOne("SHOW TABLES LIKE 'sections'")) { // If sections_old doesn't exist, check if old data is in 'sections'
            echo "WARNING: Neither 'homepage_sections' nor 'sections_old' found. Attempting to read from current 'sections' table. This might be problematic if new 'sections' table is not empty.\n";
            $old_sections_table_name = 'sections'; // Potentially problematic if new 'sections' is not empty
        } else {
             echo "Skipping sections migration as neither 'homepage_sections' nor 'sections_old' (nor 'sections' as a fallback) table found.\n";
             $old_sections = []; // Ensure $old_sections is an empty array
        }
    }

    if (isset($old_sections_table_name) && $db->queryOne("SHOW TABLES LIKE '$old_sections_table_name'")) {
        $old_sections = $db->query("SELECT section_id, title, content, background_image, section_type, data_attributes FROM $old_sections_table_name");
    } else {
        $old_sections = []; // Ensure $old_sections is an empty array if no table found
    }

    $count = 0;
    if ($old_sections) {
        foreach ($old_sections as $row) {
            $name = $row['title'];
            $content = $row['content'] ?? ''; // Default to empty string if null
            $image_url = $row['background_image'] ?? null;
            $video_url = null; // Old schema didn't have video_url directly

            // Attempt to extract video_url from data_attributes if it was stored there
            if (!empty($row['data_attributes'])) {
                $decoded_attributes = json_decode($row['data_attributes'], true);
                if (isset($decoded_attributes['video_url'])) {
                    $video_url = $decoded_attributes['video_url'];
                }
                // If content was also in data_attributes for certain types (e.g. custom_html)
                // This part needs careful mapping based on old structure.
                // For example, if section_type 'hero' had its main title in 'title' and sub-text in 'content',
                // and maybe a button text/URL in data_attributes, this needs specific logic.
                // The current simplified `sections` schema puts most textual content in `content`.
            }

            $new_section_data = [
                ':id' => $row['section_id'],
                ':name' => $name,
                ':content' => $content,
                ':image_url' => $image_url,
                ':video_url' => $video_url,
                // created_at and updated_at will use NOW()
            ];
            $db->execute(
                "INSERT INTO sections (id, name, content, image_url, video_url, created_at, updated_at)
                 VALUES (:id, :name, :content, :image_url, :video_url, NOW(), NOW())
                 ON DUPLICATE KEY UPDATE name=VALUES(name), content=VALUES(content), image_url=VALUES(image_url), video_url=VALUES(video_url), updated_at=NOW()",
                $new_section_data
            );
            $count++;
        }
    }
    echo "$count sections migrated.\n";
} catch (Exception $e) {
    echo "Error migrating sections: " . $e->getMessage() . "\n";
}
echo "------------------------------------\n";


// --- Migration for old seo_settings to new seo_settings ---
echo "Migrating old seo_settings to new seo_settings...\n";
try {
    $old_seo = $db->query("SELECT entity_type, entity_id, meta_title, meta_description, keywords AS meta_keywords FROM seo_settings_old");
    if (!$old_seo && $db->queryOne("SHOW TABLES LIKE 'seo_settings_old_temp'") ) { // Example if user renamed old table
         echo "WARNING: 'seo_settings_old' table not found. Attempting to read from 'seo_settings_old_temp' table.\n";
         $old_seo = $db->query("SELECT entity_type, entity_id, meta_title, meta_description, keywords AS meta_keywords FROM seo_settings_old_temp");
    } else if (!$old_seo) {
        echo "Skipping old SEO settings migration as 'seo_settings_old' (or temp) table not found.\n";
    }


    $count = 0;
    if ($old_seo) {
        foreach ($old_seo as $row) {
            $page_name = null;
            if ($row['entity_type'] === 'service' && !empty($row['entity_id'])) {
                $page_name = 'service_' . $row['entity_id'];
            } elseif ($row['entity_type'] === 'project' && !empty($row['entity_id'])) {
                $page_name = 'project_' . $row['entity_id'];
            } elseif ($row['entity_type'] === 'page' && !empty($row['entity_id'])) { // Assuming entity_id for pages was the page slug or name
                 // Try to map known page entity_ids (slugs/names) to new page_names
                $page_map = [
                    'home' => 'home', // if old entity_id for homepage was 'home'
                    'about-us' => 'about', // if old slug was 'about-us'
                    'contact-us' => 'contact',
                    // Add other static page mappings here
                ];
                if (isset($page_map[$row['entity_id']])) {
                    $page_name = $page_map[$row['entity_id']];
                } else {
                     // For other pages, might use entity_id directly if it's unique and descriptive
                    $page_name = 'page_' . $row['entity_id'];
                }
            }
            // Add cases for other general pages like 'services_listing', 'projects_listing' if they had SEO entries

            if ($page_name) {
                $new_seo_data = [
                    ':page_name' => $page_name,
                    ':meta_title' => $row['meta_title'],
                    ':meta_description' => $row['meta_description'],
                    ':meta_keywords' => $row['meta_keywords']
                ];
                $db->execute(
                    "INSERT INTO seo_settings (page_name, meta_title, meta_description, meta_keywords, created_at, updated_at)
                     VALUES (:page_name, :meta_title, :meta_description, :meta_keywords, NOW(), NOW())
                     ON DUPLICATE KEY UPDATE meta_title=VALUES(meta_title), meta_description=VALUES(meta_description), meta_keywords=VALUES(meta_keywords), updated_at=NOW()",
                    $new_seo_data
                );
                $count++;
            }
        }
    }
    echo "$count SEO settings migrated.\n";
} catch (Exception $e) {
    echo "Error migrating SEO settings: " . $e->getMessage() . "\n";
}
echo "------------------------------------\n";

// --- Migration for social_settings to social_links ---
echo "Migrating social_settings to social_links...\n";
try {
    $old_social = $db->query("SELECT platform, url, icon FROM social_settings_old"); // Assuming old table was social_settings
     if (!$old_social && $db->queryOne("SHOW TABLES LIKE 'social_settings'") ) {
         echo "WARNING: 'social_settings_old' table not found. Attempting to read from 'social_settings' table.\n";
         $old_social = $db->query("SELECT platform, url, icon FROM social_settings");
    }

    $count = 0;
    if ($old_social) {
        $icon_map = [ // Map old Feather icon names to Font Awesome if needed, or use directly if compatible
            'facebook' => 'fab fa-facebook-f',
            'twitter' => 'fab fa-twitter',
            'instagram' => 'fab fa-instagram',
            'linkedin' => 'fab fa-linkedin-in',
            'youtube' => 'fab fa-youtube',
            'whatsapp' => 'fab fa-whatsapp',
            // Add more mappings if other platforms were used with Feather icons
        ];
        foreach ($old_social as $row) {
            $platform_name = ucfirst($row['platform']); // e.g., Facebook
            $icon_class = $icon_map[strtolower($row['platform'])] ?? $row['icon']; // Use mapped or old icon if no map

            $new_social_link_data = [
                ':platform_name' => $platform_name,
                ':profile_url' => $row['url'],
                ':icon_class' => $icon_class,
            ];
            $db->execute(
                "INSERT INTO social_links (platform_name, profile_url, icon_class, created_at, updated_at)
                 VALUES (:platform_name, :profile_url, :icon_class, NOW(), NOW())
                 ON DUPLICATE KEY UPDATE profile_url=VALUES(profile_url), icon_class=VALUES(icon_class), updated_at=NOW()",
                $new_social_link_data
            );
            $count++;
        }
    }
    echo "$count social links migrated.\n";
} catch (Exception $e) {
    echo "Error migrating social links: " . $e->getMessage() . "\n";
}
echo "------------------------------------\n";


echo "Data migration script finished.\n";
echo "Review output for any errors.\n";
echo "Remember to manually verify data and drop old/temporary tables if necessary.\n";

?>
