-- CRM v8: "Google Ads" lead source, so website leads that came from an ad click
-- (gclid or utm_source=google&utm_medium=cpc) are labelled in Leads.
-- Safe to run more than once.
INSERT IGNORE INTO `crm_lead_sources` (`id`, `slug`, `label`) VALUES
    (10, 'google_ads', 'Google Ads');
