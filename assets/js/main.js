const API_BASE_URL = '/public/api.php'; // المسار المطلق
let UPLOAD_URL_PREFIX = 'uploads/'; // Default, will be updated from settings

// Function to fetch data from the API
async function fetchData(action, params = {}) {
    const queryParams = new URLSearchParams(params).toString();
    const url = `${API_BASE_URL}?action=${action}${queryParams ? '&' + queryParams : ''}`;
    
    console.log('📡 Fetching URL:', url);
    

    try {
        const response = await fetch(url);

        if (!response.ok) {
            console.error(`❌ API Error (${action}): ${response.status} ${response.statusText}`);
            const errorData = await response.text();
            console.error('🔍 Error details:', errorData);
            return { success: false, message: `خطأ في جلب البيانات: ${response.statusText}` };
        }

        const data = await response.json();
        // console.log(data); // Commented out to avoid excessive logging
        return data;

    } catch (error) {
        console.error(`🌐 Network Error (${action}):`, error);
        return { success: false, message: '⚠️ خطأ في الشبكة أو الاتصال بالخادم.' };
    }
}


// Initialize the site
async function initializeSite() {
    // Feather icons should be replaced after dynamic content is loaded too.
    feather.replace();
    setupEventListeners();
    await loadSiteLayoutData(); // Make sure layout is loaded before other things
    initScrollSpy();
}

// Load initial site layout data (settings, sections, etc.)
async function loadSiteLayoutData() {
    const loadingContainer = document.getElementById('homepage-content'); // Updated ID
    loadingContainer.innerHTML = `<div class="min-h-screen flex items-center justify-center"><div class="loading-spinner w-12 h-12 border-4"></div></div>`; // Adjusted spinner size

    const data = await fetchData('get_site_layout_data');

    if (data.success && data.data) {
        const { settings, sections: homepageSections, services_summary } = data.data; // Removed unused variables
        
        UPLOAD_URL_PREFIX = settings.upload_url_prefix || 'uploads/'; // Ensure this is set correctly

        // Populate site settings
        populateSiteSettings(settings);
        
        // Render homepage sections
        renderHomepageSections(homepageSections, settings); // Pass settings for map, etc.

        // Populate footer services summary (if different from main services section)
        populateFooterServices(services_summary);

        // Reinitialize AOS for dynamically added content
        if (typeof AOS !== 'undefined') {
            setTimeout(() => {
                AOS.refresh();
            }, 500);
        }

    } else {
        loadingContainer.innerHTML = `<div class="min-h-screen flex items-center justify-center text-red-500 p-4">${data.message || 'فشل تحميل بيانات الموقع.'}</div>`;
        console.error("Failed to load site layout data:", data.message);
    }
    feather.replace(); // Ensure icons are rendered after dynamic content
}

function populateSiteSettings(settings) {
    // Meta tags
    document.getElementById('meta-title').textContent = `${settings.site_name || 'حداد جده'} - ${settings.site_tagline || 'خدمات احترافية'}`;
    document.getElementById('meta-description').content = settings.site_description || 'وصف الموقع الافتراضي.';
    if (settings.site_favicon_path) {
        document.getElementById('favicon-link').href = UPLOAD_URL_PREFIX + settings.site_favicon_path;
    }

    // Header
    const logoPath = settings.site_logo_path ? UPLOAD_URL_PREFIX + settings.site_logo_path : 'https://r2.flowith.net/files/o/1748059983588-Professional_Innovative_Logo_Design_for_Metalworks_Company_index_0@1024x1024.png';
    const siteLogoHeader = document.getElementById('site-logo-header');
    if (siteLogoHeader) {
        siteLogoHeader.src = logoPath;
        siteLogoHeader.alt = `شعار ${settings.site_name || 'الموقع'}`;
    }
    document.getElementById('site-title-header').textContent = settings.site_name || 'اسم الموقع';
    document.getElementById('site-tagline-header').textContent = settings.site_tagline || 'شعار الموقع';
    
    const headerCta = document.getElementById('header-cta-button');
    if (settings.contact_phone) {
        headerCta.href = `tel:${settings.contact_phone}`;
        headerCta.style.display = 'inline-block'; // Ensure visible if phone exists
    } else {
        headerCta.style.display = 'none';
    }
    const mobileCta = document.getElementById('mobile-cta-button');
     if (settings.contact_phone) {
        mobileCta.href = `tel:${settings.contact_phone}`;
        mobileCta.style.display = 'block'; // Ensure visible if phone exists
    } else {
        mobileCta.style.display = 'none';
    }


    // Footer
    const siteLogoFooter = document.getElementById('site-logo-footer');
    if(siteLogoFooter) {
        siteLogoFooter.src = logoPath;
        siteLogoFooter.alt = `شعار ${settings.site_name || 'الموقع'} في الفوتر`;
    }
    document.getElementById('site-title-footer').textContent = settings.site_name || 'اسم الموقع';
    document.getElementById('footer-description-placeholder').textContent = settings.site_description_short || settings.site_description || 'وصف قصير للموقع.'; 

    document.getElementById('footer-contact-phone').textContent = settings.contact_phone || 'غير متوفر';
    document.getElementById('footer-contact-phone-link').href = `tel:${settings.contact_phone || ''}`;
    
    const footerContactEmail = document.getElementById('footer-contact-email');
    if (footerContactEmail && settings.contact_email) {
        footerContactEmail.innerHTML = `<span class="__cf_email__" data-cfemail="${cfEncodeEmail(settings.contact_email)}">${settings.contact_email}</span>`;
    } else if (footerContactEmail) {
        footerContactEmail.textContent = 'غير متوفر';
    }
    const footerContactEmailLink = document.getElementById('footer-contact-email-link');
    if (footerContactEmailLink) {
         footerContactEmailLink.href = `mailto:${settings.contact_email || ''}`;
    }

    document.getElementById('footer-contact-address').textContent = settings.contact_address || 'العنوان غير متوفر';
    
    const socialLinksContainer = document.getElementById('footer-social-links');
    socialLinksContainer.innerHTML = ''; // Clear placeholders
    if (settings.facebook_link) socialLinksContainer.innerHTML += `<a href="${settings.facebook_link}" target="_blank" rel="noopener noreferrer" class="footer-social-link"><i data-feather="facebook" class="w-5 h-5"></i></a>`;
    if (settings.twitter_link) socialLinksContainer.innerHTML += `<a href="${settings.twitter_link}" target="_blank" rel="noopener noreferrer" class="footer-social-link"><i data-feather="twitter" class="w-5 h-5"></i></a>`;
    if (settings.instagram_link) socialLinksContainer.innerHTML += `<a href="${settings.instagram_link}" target="_blank" rel="noopener noreferrer" class="footer-social-link"><i data-feather="instagram" class="w-5 h-5"></i></a>`;
    if (settings.whatsapp_link) socialLinksContainer.innerHTML += `<a href="${settings.whatsapp_link}" target="_blank" rel="noopener noreferrer" class="footer-social-link"><i data-feather="message-circle" class="w-5 h-5"></i></a>`; // Using message-circle for WhatsApp

    document.getElementById('copyright-site-name-footer').textContent = settings.site_name || 'الموقع';
    document.getElementById('footer-copyright-text').innerHTML = settings.footer_text || `&copy; ${new Date().getFullYear()} ${settings.site_name || 'الموقع'}. جميع الحقوق محفوظة.`;

    // Decode Cloudflare emails if present
    // Check if cf object and decode function exist before calling
    if (typeof window.cf !== 'undefined' && typeof window.cf.email !== 'undefined' && typeof window.cf.email.decode !== 'undefined') {
        cf.email.decode();
    }
    feather.replace();
}

function cfEncodeEmail(email_address) {
    let output = '';
    if (typeof email_address !== 'string') return '';
    for (let i = 0; i < email_address.length; i++) {
        output += '%' + email_address.charCodeAt(i).toString(16);
    }
    return output;
}

// *** START: Helper functions for rendering specific content items ***

function renderServiceCard(service) {
    const imageUrl = service.image ? `${UPLOAD_URL_PREFIX}${service.image}` : 'https://via.placeholder.com/400x300/f8fafc/6b7280?text=Service'; // Placeholder
    return `
        <div class="card" data-aos="fade-up">
            <div class="card-img-container">
                <img src="${imageUrl}" alt="${service.title}" class="card-img w-full h-48 object-cover">
            </div>
            <div class="card-body">
                <h3 class="card-title">${service.title}</h3>
                <p class="card-text">${service.short_description || 'وصف موجز للخدمة...'}</p>
            </div>
            <div class="card-footer">
                <button class="text-primary hover:text-primary-dark font-semibold self-start view-details-btn" data-type="service" data-slug="${service.slug}">
                    اقرأ المزيد <i data-feather="arrow-left" class="inline-block w-4 h-4"></i>
                </button>
            </div>
        </div>
    `;
}

function renderProjectCard(project) {
    const imageUrl = project.main_image ? `${UPLOAD_URL_PREFIX}${project.main_image}` : 'https://via.placeholder.com/400x300/f8fafc/6b7280?text=Project'; // Placeholder
    return `
        <div class="card" data-aos="fade-up" data-aos-delay="100">
            <div class="card-img-container">
                <img src="${imageUrl}" alt="${project.title}" class="card-img w-full h-48 object-cover">
            </div>
            <div class="card-body">
                <h3 class="card-title">${project.title}</h3>
                ${project.category ? `<span class="inline-block bg-pink-100 text-primary text-xs font-medium px-2.5 py-0.5 rounded-full mb-3">${project.category}</span>` : ''}
                <p class="card-text">${project.short_description || 'وصف موجز للمشروع...'}</p>
            </div>
            <div class="card-footer">
                <button class="text-primary hover:text-primary-dark font-semibold text-sm view-details-btn" data-type="project" data-slug="${project.slug}">
                    مشاهدة التفاصيل <i data-feather="arrow-left" class="inline-block w-4 h-4"></i>
                </button>
            </div>
        </div>
    `;
}

function renderTestimonialCard(testimonial) {
    const photoUrl = testimonial.client_photo ? `${UPLOAD_URL_PREFIX}${testimonial.client_photo}` : 'https://via.placeholder.com/100/f8fafc/6b7280?text=Client'; // Placeholder
    return `
        <div class="testimonial-card" data-aos="fade-up" data-aos-delay="150">
            <img src="${photoUrl}" alt="${testimonial.client_name}" class="testimonial-avatar">
            <p class="testimonial-content">${testimonial.feedback}</p>
            <h4 class="testimonial-author">${testimonial.client_name}</h4>
            ${testimonial.client_title_company ? `<p class="testimonial-role">${testimonial.client_title_company}</p>` : ''}
            ${testimonial.rating ? `<div class="testimonial-rating">${'★'.repeat(testimonial.rating)}${'☆'.repeat(5 - testimonial.rating)}</div>` : ''}
        </div>
    `;
}

function renderFactItem(fact) {
    return `
        <div class="fact-item" data-aos="fade-up" data-aos-delay="100" data-target="${fact.number.replace(/[^0-9.]/g, '')}">
            ${fact.icon ? `<div class="fact-icon"><i data-feather="${fact.icon}" class="w-6 h-6"></i></div>` : ''}
            <div class="fact-number">0</div>
            <h4 class="fact-title">${fact.title}</h4>
        </div>
    `;
}

// *** END: Helper functions for rendering specific content items ***


function renderHomepageSections(sections, siteSettings) {
    const container = document.getElementById('homepage-content'); // Use the main content area
    container.innerHTML = ''; // Clear loading spinner

    if (!sections || sections.length === 0) {
        container.innerHTML = '<p class="text-center py-10">لا يوجد محتوى لعرضه حالياً.</p>';
        return;
    }

    sections.forEach((section, index) => {
        // Check if section is enabled in settings
        let sectionKey = section.section_type;
        // Map section_type to key used in settings JSON
        const keyMap = {
            'about_summary': 'about',
            'services_overview': 'services',
            'projects_showcase': 'projects',
            'testimonials_slider': 'testimonials',
            'contact_info': 'contact', // contact_info and map_embed might share 'contact' key or need separate keys
            'map_embed': 'map', // Assuming 'map' key for map_embed
            'facts_counter': 'facts',
            'hero': 'hero', // Assuming 'hero' key for hero
            'call_to_action': 'cta' // Assuming 'cta' key
        };
        sectionKey = keyMap[section.section_type] || section.section_type; // Use mapped key or original type

        if (siteSettings.enabled_frontend_sections && typeof siteSettings.enabled_frontend_sections[sectionKey] !== 'undefined' && !siteSettings.enabled_frontend_sections[sectionKey]) {
            console.log(`Section type ${section.section_type} (key: ${sectionKey}) is disabled.`);
            return; // Skip rendering this section if it's disabled
        }

        const sectionElement = document.createElement('section');
        
        // Use consistent IDs for navigation
        let idForNav = sectionKey; // Use the mapped key for ID
        if (['hero', 'about', 'services', 'projects', 'testimonials', 'contact', 'facts', 'map'].includes(idForNav)) {
            sectionElement.id = idForNav;
        } else {
            // Fallback ID for other types like 'custom_html', 'call_to_action'
             sectionElement.id = section.section_type.toLowerCase().replace(/\s+/g, '-') + '-' + section.section_id;
        }
        
        // Add base section classes
        sectionElement.className = `py-12 md:py-20 relative overflow-hidden`; 
        
        // Add AOS animation to section
        sectionElement.setAttribute('data-aos', 'fade-up');
        
        // Add decorative elements for certain sections
        if (section.section_type === 'hero') {
            sectionElement.classList.add('hero-section');
            // Add decorative elements
            const decoration1 = document.createElement('div');
            decoration1.className = 'hero-decoration hero-decoration-1';
            sectionElement.appendChild(decoration1);
            
            const decoration2 = document.createElement('div');
            decoration2.className = 'hero-decoration hero-decoration-2';
            sectionElement.appendChild(decoration2);
        }
        
        // Add pattern background to alternating sections
        if (index % 2 === 0 && section.section_type !== 'hero' && !section.background_image) {
            sectionElement.classList.add('section-with-pattern');
        }
        
        // Background Handling
        if (section.background_image) {
            sectionElement.style.backgroundImage = `url('${UPLOAD_URL_PREFIX}${section.background_image}')`;
            sectionElement.style.backgroundSize = 'cover';
            sectionElement.style.backgroundPosition = 'center';
            sectionElement.style.backgroundAttachment = 'fixed';
            const overlay = document.createElement('div');
            // Adjust overlay based on section type or add a data attribute for customization
            overlay.className = 'absolute inset-0 bg-black bg-opacity-50 z-0'; 
            if (section.section_type === 'facts_counter') {
                 overlay.className = 'absolute inset-0 bg-gradient-to-b from-primary/80 to-primary-dark/90 z-0'; // Example specific overlay
            }
            sectionElement.appendChild(overlay);
            // Ensure text is readable on dark backgrounds
            sectionElement.classList.add('text-white'); 
        } else if (section.section_type === 'hero') {
            sectionElement.classList.add('hero-bg-gradient'); 
        } else {
             // Alternating background for non-image sections
            sectionElement.classList.add((index % 2 === 0) ? 'bg-white' : 'bg-light-bg');
        }


        const contentWrapper = document.createElement('div');
        contentWrapper.className = 'container mx-auto px-4 relative z-10'; 
        
        let sectionTitleHTML = '';
        // Adjust title/subtitle color based on background
        const titleColorClass = section.background_image ? 'text-white' : 'text-dark-gray';
        const subtitleColorClass = section.background_image ? 'text-gray-200' : 'text-medium-gray';

        if (section.title || section.subtitle) {
             sectionTitleHTML += `<div class="text-center mb-10 md:mb-12" data-aos="fade-up">`;
            if (section.title) {
                sectionTitleHTML += `<h2 class="section-title text-3xl md:text-4xl font-bold ${titleColorClass} mb-3">${section.title}</h2>`;
            }
            if (section.subtitle) {
                sectionTitleHTML += `<p class="section-subtitle text-lg ${subtitleColorClass} max-w-2xl mx-auto">${section.subtitle}</p>`;
            }
            sectionTitleHTML += `</div>`;
        }

        let sectionContentHTML = '';

        // *** OPTIMIZATION: Use data pre-fetched in get_site_layout_data ***
        switch (section.section_type) {
            case 'hero':
                sectionContentHTML = renderHeroSection(section, siteSettings);
                break;
            case 'about_summary':
                 // Adjust text color if background image exists
                const aboutTextColor = section.background_image ? 'text-gray-100' : 'text-text-gray';
                sectionContentHTML = `<div class="prose prose-lg max-w-3xl mx-auto text-center lg:text-right ${aboutTextColor}" data-aos="fade-up">${section.content || ''}</div>`;
                break;
            case 'services_overview':
                if (section.data_attributes?.services && section.data_attributes.services.length > 0) {
                    sectionContentHTML = `<div id="services-list" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                        ${section.data_attributes.services.map((service, idx) => renderServiceCard(service)).join('')}
                    </div>`;
                } else {
                    sectionContentHTML = '<p class="text-center text-medium-gray">لا توجد خدمات لعرضها حالياً.</p>';
                }
                break;
            case 'projects_showcase':
                 if (section.data_attributes?.projects && section.data_attributes.projects.length > 0) {
                    sectionContentHTML = `<div id="projects-list" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                        ${section.data_attributes.projects.map((project, idx) => renderProjectCard(project)).join('')}
                    </div>`;
                } else {
                    sectionContentHTML = '<p class="text-center text-medium-gray">لا توجد مشاريع لعرضها حالياً.</p>';
                }
                break;
            case 'testimonials_slider':
                 if (section.data_attributes?.testimonials && section.data_attributes.testimonials.length > 0) {
                    // Basic rendering - Slider initialization would happen later
                    sectionContentHTML = `<div id="testimonials-list" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                        ${section.data_attributes.testimonials.map((testimonial, idx) => renderTestimonialCard(testimonial)).join('')}
                    </div>`;
                    // Initialize slider after rendering
                    setTimeout(() => {
                        initTestimonialSlider();
                    }, 500);
                } else {
                    sectionContentHTML = '<p class="text-center text-medium-gray">لا توجد آراء عملاء لعرضها حالياً.</p>';
                }
                break;
            case 'facts_counter':
                 if (section.data_attributes?.facts && section.data_attributes.facts.length > 0) {
                    // Adjust text color for facts on dark background
                    const factTextColor = section.background_image ? 'text-white' : 'text-dark-gray'; 
                    sectionContentHTML = `<div id="facts-list" class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center ${factTextColor}">
                        ${section.data_attributes.facts.map(fact => renderFactItem(fact)).join('')}
                    </div>`;
                    // Initialize counter animation after rendering
                    setTimeout(() => {
                        initFactCounters();
                    }, 500);
                } else {
                    sectionContentHTML = '<p class="text-center text-medium-gray">لا توجد حقائق لعرضها حالياً.</p>';
                }
                break;
            case 'call_to_action':
                sectionContentHTML = renderCallToActionSection(section, siteSettings);
                break;
            case 'contact_info':
                sectionContentHTML = renderContactInfoSection(section, siteSettings);
                sectionContentHTML += renderTestimonialForm(); // Testimonial form part of contact section
                break;
            case 'map_embed':
                sectionContentHTML = renderMapSection(section, siteSettings);
                break;
            case 'custom_html':
                 // Adjust text color if background image exists
                const customHtmlTextColor = section.background_image ? 'text-gray-100' : 'text-text-gray';
                sectionContentHTML = `<div class="${customHtmlTextColor}" data-aos="fade-up">${section.content || ''}</div>`;
                break;
            default:
                sectionContentHTML = `<p>محتوى قسم ${section.title || 'غير مسمى'} قيد الإنشاء.</p>`;
        }
        
        contentWrapper.innerHTML = sectionTitleHTML + sectionContentHTML; // Combine title and content
        sectionElement.appendChild(contentWrapper);
        container.appendChild(sectionElement);
    });
    
    // Re-initialize feather icons after adding new elements
    feather.replace();
    
    // Initialize interactive elements like modals, sliders, counters AFTER content is in DOM
    setupModalTriggers(); 
    initScrollSpy(); // Re-initialize scroll spy after sections are rendered
}


function renderHeroSection(section, settings) {
    const ctaButtonText = section.data_attributes?.cta_button_text || 'اكتشف خدماتنا';
    const ctaButtonLink = section.data_attributes?.cta_button_link || '#services';
    const heroImage = section.data_attributes?.hero_image_url ? `<img src="${UPLOAD_URL_PREFIX}${section.data_attributes.hero_image_url}" alt="${section.title || 'صورة رئيسية'}" class="hero-image rounded-lg shadow-xl max-w-md mx-auto lg:max-w-lg">` : '';

    return `
        <div class="grid lg:grid-cols-2 items-center gap-12 min-h-[calc(80vh-5rem)] text-center lg:text-right hero-content">
            <div data-aos="fade-left" data-aos-delay="200">
                <h1 class="hero-title">
                    ${section.title || settings.site_name}
                </h1>
                <div class="hero-subtitle">${section.content || section.subtitle || settings.site_tagline}</div>
                <a href="${ctaButtonLink}" class="btn-primary">
                    ${ctaButtonText}
                </a>
            </div>
            ${heroImage ? `<div class="mt-10 lg:mt-0 hero-image-container" data-aos="fade-right" data-aos-delay="400">${heroImage}</div>` : ''}
        </div>
    `;
}

function renderCallToActionSection(section, settings) {
     const ctaButtonText = section.data_attributes?.cta_button_text || 'اتصل بنا الآن';
     const ctaButtonLink = section.data_attributes?.cta_button_link || '#contact';
     // Text color should contrast with potential background image
     const textColorClass = section.background_image ? 'text-white' : 'text-dark-gray';
     const subTextColorClass = section.background_image ? 'text-gray-200' : 'text-medium-gray';

    return `
        <div class="text-center" data-aos="zoom-in">
            <h2 class="text-3xl md:text-4xl font-bold ${textColorClass} mb-4">${section.title || 'هل أنت جاهز للبدء؟'}</h2>
            <p class="text-lg ${subTextColorClass} max-w-xl mx-auto mb-8">${section.content || 'تواصل معنا اليوم للحصول على استشارة مجانية وعرض أسعار لمشروعك.'}</p>
            <a href="${ctaButtonLink}" class="inline-block bg-white hover:bg-gray-100 text-primary px-8 py-3.5 rounded-full font-semibold text-lg smooth-transition shadow-interactive hover:shadow-lg transform hover:scale-105">
                 ${ctaButtonText}
            </a>
        </div>
    `;
}

function renderContactInfoSection(section, settings) {
     // Text color should contrast with potential background image
     const textColorClass = section.background_image ? 'text-gray-100' : 'text-text-gray';
    let html = `<div class="grid md:grid-cols-2 gap-8 ${textColorClass}">`;
    
    // Contact details side
    html += `<div data-aos="fade-left">`;
    if (section.content) {
        html += `<div class="prose max-w-none mb-6 ${textColorClass}">${section.content}</div>`;
    }
    html += `<ul class="space-y-4">`;
    if (settings.contact_phone) {
        html += `<li class="flex items-center"><i data-feather="phone" class="w-5 h-5 ml-3 text-primary"></i><a href="tel:${settings.contact_phone}" class="hover:text-primary">${settings.contact_phone}</a></li>`;
    }
    if (settings.contact_email) {
        html += `<li class="flex items-center"><i data-feather="mail" class="w-5 h-5 ml-3 text-primary"></i><a href="mailto:${settings.contact_email}" class="hover:text-primary"><span class="__cf_email__" data-cfemail="${cfEncodeEmail(settings.contact_email)}">${settings.contact_email}</span></a></li>`;
    }
    if (settings.contact_address) {
        html += `<li class="flex items-start"><i data-feather="map-pin" class="w-5 h-5 ml-3 text-primary mt-1"></i><span>${settings.contact_address}</span></li>`;
    }
    html += `</ul>`;
     // Add social links here too?
     html += `<div class="flex space-x-4 space-x-reverse mt-6">`;
     if (settings.facebook_link) html += `<a href="${settings.facebook_link}" target="_blank" rel="noopener noreferrer" class="footer-social-link"><i data-feather="facebook" class="w-6 h-6"></i></a>`;
     if (settings.twitter_link) html += `<a href="${settings.twitter_link}" target="_blank" rel="noopener noreferrer" class="footer-social-link"><i data-feather="twitter" class="w-6 h-6"></i></a>`;
     if (settings.instagram_link) html += `<a href="${settings.instagram_link}" target="_blank" rel="noopener noreferrer" class="footer-social-link"><i data-feather="instagram" class="w-6 h-6"></i></a>`;
     if (settings.whatsapp_link) html += `<a href="${settings.whatsapp_link}" target="_blank" rel="noopener noreferrer" class="footer-social-link"><i data-feather="message-circle" class="w-6 h-6"></i></a>`;
     html += `</div>`;
    html += `</div>`;

    // Placeholder for the form (rendered separately)
    html += `<div id="testimonial-form-container" data-aos="fade-right"></div>`; 

    html += `</div>`; // Close grid
    return html;
}

function renderTestimonialForm() {
    // Simple form for submitting testimonials
    return `
        <form id="testimonial-form" class="bg-white p-6 rounded-lg shadow-subtle space-y-4">
            <h3 class="text-xl font-semibold text-dark-gray mb-4">شاركنا رأيك</h3>
            <div>
                <label for="client_name" class="form-label">الاسم</label>
                <input type="text" id="client_name" name="client_name" required class="form-input">
            </div>
            <div>
                <label for="client_title_company" class="form-label">المنصب/الشركة (اختياري)</label>
                <input type="text" id="client_title_company" name="client_title_company" class="form-input">
            </div>
             <div>
                <label for="rating" class="form-label">التقييم (اختياري)</label>
                <select id="rating" name="rating" class="form-input">
                    <option value="">-- اختر تقييم --</option>
                    <option value="5">★★★★★ (ممتاز)</option>
                    <option value="4">★★★★☆ (جيد جداً)</option>
                    <option value="3">★★★☆☆ (جيد)</option>
                    <option value="2">★★☆☆☆ (مقبول)</option>
                    <option value="1">★☆☆☆☆ (سيء)</option>
                </select>
            </div>
            <div>
                <label for="feedback" class="form-label">رأيك</label>
                <textarea id="feedback" name="feedback" rows="4" required class="form-input"></textarea>
            </div>
            <button type="submit" class="w-full btn-primary">إرسال الرأي</button>
            <div id="testimonial-form-message" class="mt-4 text-sm"></div>
        </form>
    `;
}

function renderMapSection(section, settings) {
    const lat = section.data_attributes?.map_lat || settings.map_lat;
    const lng = section.data_attributes?.map_lng || settings.map_lng;
    const locationName = section.data_attributes?.map_location_name || settings.map_location_name || 'موقعنا';

    if (!lat || !lng) {
        return '<p class="text-center text-medium-gray">إحداثيات الخريطة غير متوفرة.</p>';
    }

    // Simple Google Maps Embed
    const mapUrl = `https://maps.google.com/maps?q=${lat},${lng}&hl=ar&z=15&output=embed`;
    
    return `
        <div class="aspect-w-16 aspect-h-9 rounded-lg overflow-hidden shadow-interactive" data-aos="zoom-in">
             <iframe 
                src="${mapUrl}" 
                width="100%" 
                height="450" 
                style="border:0;" 
                allowfullscreen="" 
                loading="lazy" 
                referrerpolicy="no-referrer-when-downgrade"
                title="خريطة موقع ${locationName}">
            </iframe>
        </div>
    `;
}

function populateFooterServices(services_summary) {
    const container = document.getElementById('footer-services-summary');
    if (!container || !services_summary || services_summary.length === 0) {
        if(container) container.innerHTML = '<li><a href="#services" class="footer-link">استعراض الخدمات</a></li>';
        return;
    }

    container.innerHTML = services_summary
        .map(service => `<li><a href="#services" data-slug="${service.slug}" class="footer-link">${service.title}</a></li>`)
        .join('');
}


// --- Event Listeners Setup ---
function setupEventListeners() {
    // Mobile Menu Toggle
    const menuBtn = document.getElementById('mobile-menu-btn');
    const mobileMenu = document.getElementById('mobile-menu');
    if (menuBtn && mobileMenu) {
        menuBtn.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
            // Change icon based on state
            const icon = menuBtn.querySelector('i');
            if (mobileMenu.classList.contains('hidden')) {
                icon.setAttribute('data-feather', 'menu');
            } else {
                icon.setAttribute('data-feather', 'x');
            }
            feather.replace(); // Update the icon
        });
    }

    // Close mobile menu when a link is clicked
    const mobileNavLinks = document.querySelectorAll('#mobile-nav-links a');
    mobileNavLinks.forEach(link => {
        link.addEventListener('click', () => {
            if (mobileMenu && !mobileMenu.classList.contains('hidden')) {
                 mobileMenu.classList.add('hidden');
                 menuBtn.querySelector('i').setAttribute('data-feather', 'menu');
                 feather.replace();
            }
        });
    });

    // Back to Top Button
    const backToTopBtn = document.getElementById('back-to-top');
    if (backToTopBtn) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 300) {
                backToTopBtn.classList.remove('opacity-0', 'invisible');
                backToTopBtn.classList.add('opacity-100', 'visible');
            } else {
                backToTopBtn.classList.remove('opacity-100', 'visible');
                backToTopBtn.classList.add('opacity-0', 'invisible');
            }
        });
        backToTopBtn.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    // Header Shrink on Scroll
    const header = document.getElementById('header');
    if (header) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 50) {
                header.classList.add('py-2', 'shadow-md', 'scrolled');
                header.classList.remove('py-3');
            } else {
                header.classList.remove('py-2', 'shadow-md', 'scrolled');
                header.classList.add('py-3');
            }
        });
    }

    // Testimonial Form Submission
    const testimonialForm = document.getElementById('testimonial-form');
    if (testimonialForm) {
        testimonialForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const messageDiv = document.getElementById('testimonial-form-message');
            messageDiv.textContent = 'جاري الإرسال...';
            messageDiv.className = 'mt-4 text-sm text-blue-600';

            const formData = new FormData(testimonialForm);
            
            try {
                // Use fetch to submit form data via POST
                const response = await fetch(`${API_BASE_URL}?action=submit_testimonial`, {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.success) {
                    messageDiv.textContent = result.message;
                    messageDiv.className = 'mt-4 text-sm text-green-600';
                    testimonialForm.reset();
                } else {
                    messageDiv.textContent = result.message || 'حدث خطأ أثناء الإرسال.';
                    messageDiv.className = 'mt-4 text-sm text-red-600';
                }
            } catch (error) {
                console.error('Testimonial submission error:', error);
                messageDiv.textContent = 'حدث خطأ في الشبكة. يرجى المحاولة مرة أخرى.';
                messageDiv.className = 'mt-4 text-sm text-red-600';
            }
        });
    }
    
    // Smooth scrolling for internal links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const href = this.getAttribute('href');
            // Ensure it's not just a placeholder '#' link
            if (href.length > 1) { 
                const targetElement = document.querySelector(href);
                if (targetElement) {
                    e.preventDefault();
                    const headerOffset = document.getElementById('header')?.offsetHeight || 70; // Adjust for fixed header
                    const elementPosition = targetElement.getBoundingClientRect().top;
                    const offsetPosition = elementPosition + window.pageYOffset - headerOffset;

                    window.scrollTo({
                        top: offsetPosition,
                        behavior: "smooth"
                    });
                    
                    // Update active nav link (optional, handled by scroll spy too)
                    // updateActiveNavLink(href);
                }
            }
        });
    });

    // Initial setup for modal triggers (will be called again after content loads)
    setupModalTriggers();
}

// --- Modal Handling ---
const modal = document.getElementById('details-modal');
const modalTitle = document.getElementById('modal-title');
const modalBody = document.getElementById('modal-body');
const modalImages = document.getElementById('modal-images');
const modalCloseBtn = document.getElementById('modal-close-btn');

function openModal() {
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden'; // Prevent background scrolling
    }
}

function closeModal() {
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = ''; // Restore scrolling
        // Clear modal content
        modalTitle.textContent = '';
        modalBody.innerHTML = '';
        modalImages.innerHTML = '';
    }
}

function setupModalTriggers() {
    // Remove existing listeners to avoid duplicates if called multiple times
    document.querySelectorAll('.view-details-btn').forEach(button => {
        button.replaceWith(button.cloneNode(true)); // Simple way to remove listeners
    });
    
    // Add new listeners
    document.querySelectorAll('.view-details-btn').forEach(button => {
        button.addEventListener('click', async () => {
            const type = button.dataset.type;
            const slug = button.dataset.slug;
            if (!type || !slug) return;

            modalBody.innerHTML = '<div class="loading-spinner w-8 h-8 mx-auto"></div>';
            modalTitle.textContent = 'جاري التحميل...';
            openModal();

            let data = null;
            if (type === 'service') {
                data = await fetchData('get_service_details', { slug: slug });
            } else if (type === 'project') {
                data = await fetchData('get_project_details', { slug: slug });
            }

            if (data && data.success && data.data) {
                const item = data.data.service || data.data.project;
                modalTitle.textContent = item.title;
                modalBody.innerHTML = item.full_description || item.short_description || 'لا توجد تفاصيل إضافية.';
                
                // Handle project images
                modalImages.innerHTML = '';
                if (type === 'project' && item.additional_images && item.additional_images.length > 0) {
                    modalImages.innerHTML = '<h4 class="text-lg font-semibold mt-6 mb-3">صور إضافية:</h4>';
                    const imageGrid = document.createElement('div');
                    imageGrid.className = 'image-gallery';
                    item.additional_images.forEach(img => {
                        imageGrid.innerHTML += `
                            <div class="gallery-item">
                                <img src="${UPLOAD_URL_PREFIX}${img.image_path}" alt="${img.caption || item.title}" onclick="openImageInNewTab('${UPLOAD_URL_PREFIX}${img.image_path}')">
                                ${img.caption ? `<div class="gallery-caption">${img.caption}</div>` : ''}
                            </div>
                        `;
                    });
                    modalImages.appendChild(imageGrid);
                }
                
            } else {
                modalTitle.textContent = 'خطأ';
                modalBody.innerHTML = `<p class="text-red-500">${data.message || 'فشل تحميل التفاصيل.'}</p>`;
            }
        });
    });

    if (modalCloseBtn) {
        modalCloseBtn.addEventListener('click', closeModal);
    }

    // Close modal on overlay click
    if (modal) {
        modal.addEventListener('click', (event) => {
            if (event.target === modal) { // Check if click is on the overlay itself
                closeModal();
            }
        });
    }
}

// Helper to open image in new tab (used in modal)
function openImageInNewTab(url) {
    window.open(url, '_blank');
}


// --- Scroll Spy for Active Nav Link ---
function initScrollSpy() {
    const sections = document.querySelectorAll('section[id]');
    const navLinks = document.querySelectorAll('.nav-link'); // Desktop and mobile links share this class
    const headerOffset = document.getElementById('header')?.offsetHeight || 70;

    if (!sections.length || !navLinks.length) return;

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const id = entry.target.getAttribute('id');
                // Remove active class from all links
                navLinks.forEach(link => link.classList.remove('active', 'text-primary'));
                // Add active class to the corresponding link(s)
                document.querySelectorAll(`.nav-link[href="#${id}"]`).forEach(activeLink => {
                    activeLink.classList.add('active', 'text-primary');
                });
            }
        });
    }, {
        rootMargin: `-${headerOffset}px 0px 0px 0px`, // Adjust rootMargin to account for fixed header
        threshold: 0.3 // Adjust threshold as needed
    });

    sections.forEach(section => {
        observer.observe(section);
    });
}


// --- Fact Counter Animation ---
function initFactCounters() {
    const factItems = document.querySelectorAll('.fact-item');
    if (!factItems.length) return;

    const observer = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const item = entry.target;
                const numberElement = item.querySelector('.fact-number');
                const target = +item.dataset.target;
                const prefix = item.dataset.prefix || '';
                const suffix = item.dataset.suffix || '';
                
                if (!target || numberElement.classList.contains('counted')) return;

                numberElement.classList.add('counted');
                let current = 0;
                const increment = target / 100; // Adjust speed by changing divisor

                const updateCounter = () => {
                    current += increment;
                    if (current < target) {
                        numberElement.textContent = prefix + Math.ceil(current) + suffix;
                        requestAnimationFrame(updateCounter);
                    } else {
                        numberElement.textContent = prefix + target + suffix; // Ensure final value is exact
                    }
                };
                updateCounter();
                observer.unobserve(item); // Stop observing once animated
            }
        });
    }, { threshold: 0.5 }); // Trigger when 50% visible

    factItems.forEach(item => {
        observer.observe(item);
    });
}

// --- Testimonial Slider ---
function initTestimonialSlider() {
    const sliderContainer = document.getElementById('testimonials-list');
    if (!sliderContainer || sliderContainer.children.length < 2) return; // No need for slider with < 2 items

    // Add touch swipe functionality
    let startX, endX;
    const testimonials = Array.from(sliderContainer.children);
    let currentIndex = 0;
    
    // Add navigation dots
    const dotsContainer = document.createElement('div');
    dotsContainer.className = 'flex justify-center mt-6 space-x-2 space-x-reverse';
    
    testimonials.forEach((_, index) => {
        const dot = document.createElement('button');
        dot.className = `w-3 h-3 rounded-full ${index === 0 ? 'bg-primary' : 'bg-gray-300'}`;
        dot.addEventListener('click', () => goToSlide(index));
        dotsContainer.appendChild(dot);
    });
    
    sliderContainer.parentNode.appendChild(dotsContainer);
    
    // Add navigation arrows
    const arrowsContainer = document.createElement('div');
    arrowsContainer.className = 'flex justify-between absolute top-1/2 transform -translate-y-1/2 left-0 right-0 px-4';
    
    const prevArrow = document.createElement('button');
    prevArrow.className = 'bg-white rounded-full shadow-lg p-2 text-primary hover:text-primary-dark focus:outline-none';
    prevArrow.innerHTML = '<i data-feather="chevron-right" class="w-6 h-6"></i>';
    prevArrow.addEventListener('click', () => goToSlide(currentIndex - 1));
    
    const nextArrow = document.createElement('button');
    nextArrow.className = 'bg-white rounded-full shadow-lg p-2 text-primary hover:text-primary-dark focus:outline-none';
    nextArrow.innerHTML = '<i data-feather="chevron-left" class="w-6 h-6"></i>';
    nextArrow.addEventListener('click', () => goToSlide(currentIndex + 1));
    
    arrowsContainer.appendChild(prevArrow);
    arrowsContainer.appendChild(nextArrow);
    
    sliderContainer.parentNode.appendChild(arrowsContainer);
    feather.replace();
    
    // Setup touch events
    sliderContainer.addEventListener('touchstart', (e) => {
        startX = e.touches[0].clientX;
    });
    
    sliderContainer.addEventListener('touchend', (e) => {
        endX = e.changedTouches[0].clientX;
        handleSwipe();
    });
    
    function handleSwipe() {
        const threshold = 50; // Minimum distance to be considered a swipe
        if (startX - endX > threshold) {
            // Swipe left, go to next
            goToSlide(currentIndex + 1);
        } else if (endX - startX > threshold) {
            // Swipe right, go to previous
            goToSlide(currentIndex - 1);
        }
    }
    
    function goToSlide(index) {
        // Handle circular navigation
        if (index < 0) index = testimonials.length - 1;
        if (index >= testimonials.length) index = 0;
        
        // Update current index
        currentIndex = index;
        
        // Update dots
        const dots = dotsContainer.querySelectorAll('button');
        dots.forEach((dot, i) => {
            dot.className = `w-3 h-3 rounded-full ${i === index ? 'bg-primary' : 'bg-gray-300'}`;
        });
        
        // Show only current testimonial on mobile
        if (window.innerWidth < 768) {
            testimonials.forEach((testimonial, i) => {
                testimonial.style.display = i === index ? 'block' : 'none';
            });
        }
    }
    
    // Initialize for mobile
    if (window.innerWidth < 768) {
        goToSlide(0);
    }
    
    // Handle window resize
    window.addEventListener('resize', () => {
        if (window.innerWidth < 768) {
            goToSlide(currentIndex);
        } else {
            // Reset display for desktop
            testimonials.forEach(testimonial => {
                testimonial.style.display = 'block';
            });
        }
    });
}

// Initialize the site on DOMContentLoaded
document.addEventListener('DOMContentLoaded', initializeSite);
