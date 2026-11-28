<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once dirname(__FILE__).'/ymo_host.php';

/*
|--------------------------------------------------------------------------
| URI ROUTING
|--------------------------------------------------------------------------
*/

$route['translate_uri_dashes'] = FALSE;
$route['404_override']         = 'errors/show_404';

// -----------------------------------------------------------------------
// ADMIN HOST — short URIs (/login, /dashboard, …); no booking routes.
// -----------------------------------------------------------------------
if (ymo_is_admin_host_request()) {

    $route['default_controller'] = 'admin/dashboard/index';

    $route['login']   = 'admin/auth/login';
    $route['logout']  = 'admin/auth/logout';
    $route['dashboard'] = 'admin/dashboard';

    $route['bookings']                        = 'admin/bookings/index';
    $route['bookings/new'] = 'admin/bookings/create';
    $route['bookings/lookup'] = 'admin/bookings/lookup';
    $route['bookings/(:num)']                 = 'admin/bookings/view/$1';
    $route['job-cards'] = 'admin/work_orders/index';
    $route['job-cards/new'] = 'admin/work_orders/create';
    $route['job-cards/catalog-search'] = 'admin/work_orders/catalog_search';
    $route['job-cards/(:num)/update'] = 'admin/work_orders/update/$1';
    $route['job-cards/(:num)/status'] = 'admin/work_orders/status/$1';
    $route['job-cards/(:num)/delete'] = 'admin/work_orders/delete/$1';
    $route['job-cards/(:num)/print'] = 'admin/work_orders/print_card/$1';
    $route['job-cards/(:num)/lines/add'] = 'admin/work_orders/line_add/$1';
    $route['job-cards/(:num)/lines/(:num)/update'] = 'admin/work_orders/line_update/$1/$2';
    $route['job-cards/(:num)/lines/(:num)/delete'] = 'admin/work_orders/line_delete/$1/$2';
    $route['job-cards/(:num)/lines/(:num)/done'] = 'admin/work_orders/line_done/$1/$2';
    $route['job-cards/(:num)/note'] = 'admin/work_orders/note/$1';
    $route['job-cards/(:num)/photos'] = 'admin/work_orders/photo_add/$1';
    $route['job-cards/(:num)/photos/(:num)/delete'] = 'admin/work_orders/photo_delete/$1/$2';
    $route['job-cards/photo/(:num)'] = 'admin/work_orders/photo/$1';
    $route['technicians'] = 'admin/technicians/index';
    $route['technicians/new'] = 'admin/technicians/create';
    $route['technicians/(:num)/edit'] = 'admin/technicians/edit/$1';
    $route['technicians/(:num)/signout'] = 'admin/technicians/signout/$1';
    $route['tech'] = 'tech/index';
    $route['tech/sw.js'] = 'tech/sw';
    $route['tech/manifest.webmanifest'] = 'tech/manifest';
    $route['tech/icon.svg'] = 'tech/icon';
    $route['tech/photo/(:num)'] = 'tech/photo/$1';
    $route['tech/api/login'] = 'tech/api_login';
    $route['tech/api/logout'] = 'tech/api_logout';
    $route['tech/api/me'] = 'tech/api_me';
    $route['tech/api/lang'] = 'tech/api_lang';
    $route['tech/api/jobs'] = 'tech/api_jobs';
    $route['tech/api/jobs/(:num)'] = 'tech/api_job/$1';
    $route['tech/api/jobs/(:num)/lines/(:num)'] = 'tech/api_line/$1/$2';
    $route['tech/api/jobs/(:num)/status'] = 'tech/api_status/$1';
    $route['tech/api/jobs/(:num)/note'] = 'tech/api_note/$1';
    $route['tech/api/jobs/(:num)/photos'] = 'tech/api_photo/$1';
    $route['job-cards/(:num)'] = 'admin/work_orders/view/$1';
    $route['bookings/(:num)/status']          = 'admin/bookings/update_status/$1';
    $route['bookings/(:num)/send-review']     = 'admin/bookings/send_review/$1';
    $route['bookings/(:num)/invoice']         = 'admin/bookings/create_invoice/$1';
    $route['bookings/(:num)/invoice/(:num)/edit'] = 'admin/bookings/edit_invoice/$1/$2';
    $route['bookings/(:num)/invoice/(:num)/pdf'] = 'admin/bookings/download_invoice/$1/$2';
    $route['bookings/(:num)/whatsapp'] = 'admin/bookings/whatsapp_update/$1';
    $route['bookings/(:num)/whatsapp/media/(:num)'] = 'admin/bookings/whatsapp_media/$1/$2';

    $route['online-accounts']                 = 'admin/online_accounts/index';
    $route['online-accounts/(:num)']          = 'admin/online_accounts/view/$1';

    $route['customers']                       = 'admin/contacts/index';
    $route['customers/new']                   = 'admin/contacts/create';
    $route['customers/import']                = 'admin/contacts/import';
    $route['customers/import/template']       = 'admin/contacts/import_template';
    $route['customers/import/preview']        = 'admin/contacts/import_preview';
    $route['customers/import/commit']         = 'admin/contacts/import_commit';
    $route['customers/bulk-edit']             = 'admin/contacts/bulk_edit';
    $route['customers/export']                = 'admin/contacts/export';
    $route['customers/(:num)/link-user']      = 'admin/contacts/link_user/$1';
    $route['customers/(:num)/edit']           = 'admin/contacts/edit/$1';
    $route['customers/(:num)']                = 'admin/contacts/view/$1';

    $route['packages']                        = 'admin/packages/index';
    $route['packages/new']                    = 'admin/packages/create';
    $route['packages/(:num)/edit']           = 'admin/packages/edit/$1';
    $route['packages/(:num)/delete']         = 'admin/packages/delete/$1';
    $route['catalogue'] = 'admin/catalogue/index';
    $route['catalogue/new'] = 'admin/catalogue/create';
    $route['catalogue/(:num)/edit'] = 'admin/catalogue/edit/$1';
    $route['catalogue/(:num)/toggle'] = 'admin/catalogue/toggle/$1';

    $route['offers']                          = 'admin/offers/index';
    $route['offers/new']                      = 'admin/offers/create';
    $route['offers/(:num)/edit']             = 'admin/offers/edit/$1';
    $route['offers/(:num)/delete']           = 'admin/offers/delete/$1';

    $route['settings']                       = 'admin/settings/index';

    $route['team']                           = 'admin/team/index';
    $route['team/new']                       = 'admin/team/create';
    $route['team/(:num)/edit']               = 'admin/team/edit/$1';
    $route['team/(:num)/reset-password']     = 'admin/team/reset_password/$1';
    $route['team/(:num)/deactivate']         = 'admin/team/deactivate/$1';
    $route['team/(:num)/activate']           = 'admin/team/activate/$1';

    $route['roles']                          = 'admin/roles/index';
    $route['roles/new']                      = 'admin/roles/create';
    $route['roles/(:num)/edit']              = 'admin/roles/edit/$1';
    $route['roles/(:num)/delete']            = 'admin/roles/delete/$1';

    $route['leads']                            = 'admin/leads/index';
    $route['leads/pipeline']                   = 'admin/leads/pipeline';
    $route['leads/new']                        = 'admin/leads/create';
    $route['leads/(:num)']                     = 'admin/leads/view/$1';
    $route['leads/(:num)/edit']                = 'admin/leads/edit/$1';
    $route['leads/(:num)/assign']              = 'admin/leads/assign/$1';
    $route['leads/(:num)/activity']            = 'admin/leads/add_activity/$1';
    $route['leads/(:num)/send-chat']           = 'admin/leads/send_chat/$1';
    $route['leads/(:num)/log'] = 'admin/leads/log_call/$1';
    $route['leads/(:num)/stage']               = 'admin/leads/update_stage/$1';
    $route['leads/(:num)/archive']             = 'admin/leads/archive/$1';
    $route['leads/(:num)/convert']             = 'admin/leads/convert/$1';

    $route['tasks']                            = 'admin/tasks/index';
    $route['tasks/new']                        = 'admin/tasks/create';
    $route['tasks/(:num)/edit']                = 'admin/tasks/edit/$1';
    $route['tasks/(:num)/snooze'] = 'admin/tasks/snooze/$1';
    $route['tasks/(:num)/done']                = 'admin/tasks/done/$1';
    $route['tasks/(:num)/skip']                = 'admin/tasks/skip/$1';

    $route['contacts']                         = 'admin/contacts/index';
    $route['contacts/new']                     = 'admin/contacts/create';
    $route['contacts/import']                  = 'admin/contacts/import';
    $route['contacts/import/template']         = 'admin/contacts/import_template';
    $route['contacts/import/preview']          = 'admin/contacts/import_preview';
    $route['contacts/import/commit']           = 'admin/contacts/import_commit';
    $route['contacts/bulk-edit']              = 'admin/contacts/bulk_edit';
    $route['contacts/export']                  = 'admin/contacts/export';
    $route['contacts/(:num)']                  = 'admin/contacts/view/$1';
    $route['contacts/(:num)/edit']             = 'admin/contacts/edit/$1';

    $route['campaigns']                        = 'admin/campaigns/index';
    $route['google-posts'] = 'admin/gbp_posts/index';
    $route['google-posts/save'] = 'admin/gbp_posts/save';
    $route['google-posts/(:num)/publish'] = 'admin/gbp_posts/publish/$1';
    $route['google-posts/(:num)/delete'] = 'admin/gbp_posts/delete/$1';
    $route['reviews'] = 'admin/reviews/index';
    $route['reviews/connect'] = 'admin/reviews/connect';
    $route['reviews/google/callback'] = 'admin/reviews/callback';
    $route['reviews/disconnect'] = 'admin/reviews/disconnect';
    $route['reviews/locations'] = 'admin/reviews/locations';
    $route['reviews/credentials'] = 'admin/reviews/credentials';
    $route['reviews/check'] = 'admin/reviews/check';
    $route['reviews/ask'] = 'admin/reviews/ask';
    $route['reviews/ask-bulk'] = 'admin/reviews/ask_bulk';
    $route['reviews/(:num)/reply'] = 'admin/reviews/reply/$1';
    $route['reviews/(:num)/done'] = 'admin/reviews/done/$1';
    $route['ymochat/app-manifest'] = 'api/ymochat/manifest';
    $route['ymochat/push/key'] = 'admin/ymochat/push_key';
    $route['ymochat/push/subscribe'] = 'admin/ymochat/push_subscribe';
    $route['ymochat/push/state'] = 'admin/ymochat/push_state';
    $route['ymochat/push/unsubscribe'] = 'admin/ymochat/push_unsubscribe';
    $route['ymochat/push/test'] = 'admin/ymochat/push_test';
    $route['ymochat'] = 'admin/ymochat/index';
    $route['ymochat/feed'] = 'admin/ymochat/feed';
    $route['ymochat/pulse'] = 'admin/ymochat/pulse';
    $route['ymochat/(:num)/send'] = 'admin/ymochat/send/$1';
    $route['ymochat/(:num)'] = 'admin/ymochat/thread/$1';
    $route['inbox'] = 'admin/inbox/index';
    $route['inbox/unread'] = 'admin/inbox/unread';
    $route['inbox/read-all'] = 'admin/inbox/read_all';
    $route['inbox/(:num)/reply'] = 'admin/inbox/reply/$1';
    $route['inbox/(:num)'] = 'admin/inbox/view/$1';
    $route['campaigns/delete'] = 'admin/campaigns/bulk_delete';
    $route['campaigns/new']                    = 'admin/campaigns/create';
    $route['campaigns/(:num)']                 = 'admin/campaigns/view/$1';
    $route['campaigns/(:num)/edit']            = 'admin/campaigns/edit/$1';
    $route['campaigns/(:num)/test'] = 'admin/campaigns/send_test/$1';
    $route['campaigns/(:num)/duplicate'] = 'admin/campaigns/duplicate/$1';
    $route['campaigns/(:num)/retry'] = 'admin/campaigns/retry/$1';
    $route['campaigns/(:num)/no-whatsapp'] = 'admin/campaigns/no_whatsapp/$1';
    $route['campaigns/(:num)/export'] = 'admin/campaigns/export/$1';
    $route['campaigns/(:num)/send']            = 'admin/campaigns/send/$1';
    $route['campaigns/(:num)/schedule']        = 'admin/campaigns/schedule/$1';
    $route['whatsapp-templates'] = 'admin/wa_templates/index';
    $route['whatsapp-templates/sync'] = 'admin/wa_templates/sync';
    $route['whatsapp-templates/submit'] = 'admin/wa_templates/submit';

    $route['recruitment']                      = 'admin/recruitment/index';
    $route['recruitment/new']                  = 'admin/recruitment/create';
    $route['recruitment/(:num)']               = 'admin/recruitment/view/$1';
    $route['recruitment/(:num)/edit']          = 'admin/recruitment/edit/$1';
    $route['recruitment/(:num)/upload']        = 'admin/recruitment/upload_resume/$1';
    $route['recruitment/document/(:num)']      = 'admin/recruitment/document/$1';
    $route['recruitment/(:num)/schedule']     = 'admin/recruitment/schedule_interview/$1';

    $route['reports']                          = 'admin/reports/index';
    $route['reports/export/leads']             = 'admin/reports/export_leads';
    $route['reports/export/service-due']       = 'admin/reports/export_service_due';
    $route['reports/export/revenue']           = 'admin/reports/export_revenue';

    $route['api/webhooks/meta']                = 'api/webhooks/meta';
    $route['api/webhooks/website']             = 'api/webhooks/website';
    $route['api/webhooks/whatsapp']            = 'api/webhooks/whatsapp';
    $route['api/offers/active']                = 'api/offers/active';
    $route['api/offers/preview/(:num)']      = 'api/offers/preview/$1';

    // Keep legacy prefixed paths resolving (helps old bookmarks/emails).
    $route['admin']                          = 'admin/dashboard';
    $route['admin/login']                     = 'admin/auth/login';
    $route['admin/logout']                    = 'admin/auth/logout';
    $route['admin/dashboard']                 = 'admin/dashboard';
    $route['admin/bookings']                   = 'admin/bookings/index';
    $route['admin/bookings/new'] = 'admin/bookings/create';
    $route['admin/bookings/lookup'] = 'admin/bookings/lookup';
    $route['admin/bookings/(:num)']            = 'admin/bookings/view/$1';
    $route['admin/job-cards'] = 'admin/work_orders/index';
    $route['admin/job-cards/new'] = 'admin/work_orders/create';
    $route['admin/job-cards/catalog-search'] = 'admin/work_orders/catalog_search';
    $route['admin/job-cards/(:num)/update'] = 'admin/work_orders/update/$1';
    $route['admin/job-cards/(:num)/status'] = 'admin/work_orders/status/$1';
    $route['admin/job-cards/(:num)/delete'] = 'admin/work_orders/delete/$1';
    $route['admin/job-cards/(:num)/print'] = 'admin/work_orders/print_card/$1';
    $route['admin/job-cards/(:num)/lines/add'] = 'admin/work_orders/line_add/$1';
    $route['admin/job-cards/(:num)/lines/(:num)/update'] = 'admin/work_orders/line_update/$1/$2';
    $route['admin/job-cards/(:num)/lines/(:num)/delete'] = 'admin/work_orders/line_delete/$1/$2';
    $route['admin/job-cards/(:num)/lines/(:num)/done'] = 'admin/work_orders/line_done/$1/$2';
    $route['admin/job-cards/(:num)/note'] = 'admin/work_orders/note/$1';
    $route['admin/job-cards/(:num)/photos'] = 'admin/work_orders/photo_add/$1';
    $route['admin/job-cards/(:num)/photos/(:num)/delete'] = 'admin/work_orders/photo_delete/$1/$2';
    $route['admin/job-cards/photo/(:num)'] = 'admin/work_orders/photo/$1';
    $route['admin/technicians'] = 'admin/technicians/index';
    $route['admin/technicians/new'] = 'admin/technicians/create';
    $route['admin/technicians/(:num)/edit'] = 'admin/technicians/edit/$1';
    $route['admin/technicians/(:num)/signout'] = 'admin/technicians/signout/$1';
    $route['admin/tech'] = 'tech/index';
    $route['admin/tech/sw.js'] = 'tech/sw';
    $route['admin/tech/manifest.webmanifest'] = 'tech/manifest';
    $route['admin/tech/icon.svg'] = 'tech/icon';
    $route['admin/tech/photo/(:num)'] = 'tech/photo/$1';
    $route['admin/tech/api/login'] = 'tech/api_login';
    $route['admin/tech/api/logout'] = 'tech/api_logout';
    $route['admin/tech/api/me'] = 'tech/api_me';
    $route['admin/tech/api/lang'] = 'tech/api_lang';
    $route['admin/tech/api/jobs'] = 'tech/api_jobs';
    $route['admin/tech/api/jobs/(:num)'] = 'tech/api_job/$1';
    $route['admin/tech/api/jobs/(:num)/lines/(:num)'] = 'tech/api_line/$1/$2';
    $route['admin/tech/api/jobs/(:num)/status'] = 'tech/api_status/$1';
    $route['admin/tech/api/jobs/(:num)/note'] = 'tech/api_note/$1';
    $route['admin/tech/api/jobs/(:num)/photos'] = 'tech/api_photo/$1';
    $route['admin/job-cards/(:num)'] = 'admin/work_orders/view/$1';
    $route['admin/bookings/(:num)/status']     = 'admin/bookings/update_status/$1';
    $route['admin/bookings/(:num)/send-review'] = 'admin/bookings/send_review/$1';
    $route['admin/bookings/(:num)/invoice'] = 'admin/bookings/create_invoice/$1';
    $route['admin/bookings/(:num)/invoice/(:num)/edit'] = 'admin/bookings/edit_invoice/$1/$2';
    $route['admin/bookings/(:num)/invoice/(:num)/pdf'] = 'admin/bookings/download_invoice/$1/$2';
    $route['admin/bookings/(:num)/whatsapp'] = 'admin/bookings/whatsapp_update/$1';
    $route['admin/bookings/(:num)/whatsapp/media/(:num)'] = 'admin/bookings/whatsapp_media/$1/$2';
    $route['admin/online-accounts']           = 'admin/online_accounts/index';
    $route['admin/online-accounts/(:num)']    = 'admin/online_accounts/view/$1';
    $route['admin/customers']                 = 'admin/contacts/index';
    $route['admin/customers/new']             = 'admin/contacts/create';
    $route['admin/customers/import']          = 'admin/contacts/import';
    $route['admin/customers/import/template'] = 'admin/contacts/import_template';
    $route['admin/customers/import/preview']  = 'admin/contacts/import_preview';
    $route['admin/customers/import/commit']   = 'admin/contacts/import_commit';
    $route['admin/customers/bulk-edit']       = 'admin/contacts/bulk_edit';
    $route['admin/customers/export']          = 'admin/contacts/export';
    $route['admin/customers/(:num)/link-user']= 'admin/contacts/link_user/$1';
    $route['admin/customers/(:num)/edit']     = 'admin/contacts/edit/$1';
    $route['admin/customers/(:num)']          = 'admin/contacts/view/$1';
    $route['admin/packages']                  = 'admin/packages/index';
    $route['admin/packages/new']               = 'admin/packages/create';
    $route['admin/packages/(:num)/edit']      = 'admin/packages/edit/$1';
    $route['admin/packages/(:num)/delete']    = 'admin/packages/delete/$1';
    $route['admin/catalogue'] = 'admin/catalogue/index';
    $route['admin/catalogue/new'] = 'admin/catalogue/create';
    $route['admin/catalogue/(:num)/edit'] = 'admin/catalogue/edit/$1';
    $route['admin/catalogue/(:num)/toggle'] = 'admin/catalogue/toggle/$1';
    $route['admin/offers']                    = 'admin/offers/index';
    $route['admin/offers/new']                = 'admin/offers/create';
    $route['admin/offers/(:num)/edit']        = 'admin/offers/edit/$1';
    $route['admin/offers/(:num)/delete']      = 'admin/offers/delete/$1';
    $route['admin/settings']                 = 'admin/settings/index';
    $route['admin/team']                     = 'admin/team/index';
    $route['admin/team/new']                 = 'admin/team/create';
    $route['admin/team/(:num)/edit']         = 'admin/team/edit/$1';
    $route['admin/team/(:num)/reset-password'] = 'admin/team/reset_password/$1';
    $route['admin/team/(:num)/deactivate']   = 'admin/team/deactivate/$1';
    $route['admin/team/(:num)/activate']     = 'admin/team/activate/$1';
    $route['admin/roles']                    = 'admin/roles/index';
    $route['admin/roles/new']                = 'admin/roles/create';
    $route['admin/roles/(:num)/edit']        = 'admin/roles/edit/$1';
    $route['admin/roles/(:num)/delete']      = 'admin/roles/delete/$1';
    $route['admin/leads']                      = 'admin/leads/index';
    $route['admin/leads/pipeline']             = 'admin/leads/pipeline';
    $route['admin/leads/new']                  = 'admin/leads/create';
    $route['admin/leads/(:num)']               = 'admin/leads/view/$1';
    $route['admin/leads/(:num)/edit']          = 'admin/leads/edit/$1';
    $route['admin/leads/(:num)/assign']        = 'admin/leads/assign/$1';
    $route['admin/leads/(:num)/activity']      = 'admin/leads/add_activity/$1';
    $route['admin/leads/(:num)/send-chat']     = 'admin/leads/send_chat/$1';
    $route['admin/leads/(:num)/log'] = 'admin/leads/log_call/$1';
    $route['admin/leads/(:num)/stage']         = 'admin/leads/update_stage/$1';
    $route['admin/leads/(:num)/archive']       = 'admin/leads/archive/$1';
    $route['admin/leads/(:num)/convert']       = 'admin/leads/convert/$1';
    $route['admin/tasks']                      = 'admin/tasks/index';
    $route['admin/tasks/new']                  = 'admin/tasks/create';
    $route['admin/tasks/(:num)/edit']          = 'admin/tasks/edit/$1';
    $route['admin/tasks/(:num)/snooze'] = 'admin/tasks/snooze/$1';
    $route['admin/tasks/(:num)/done']          = 'admin/tasks/done/$1';
    $route['admin/tasks/(:num)/skip']          = 'admin/tasks/skip/$1';
    $route['admin/contacts']                   = 'admin/contacts/index';
    $route['admin/contacts/new']               = 'admin/contacts/create';
    $route['admin/contacts/import']            = 'admin/contacts/import';
    $route['admin/contacts/import/template']   = 'admin/contacts/import_template';
    $route['admin/contacts/import/preview']    = 'admin/contacts/import_preview';
    $route['admin/contacts/import/commit']     = 'admin/contacts/import_commit';
    $route['admin/contacts/bulk-edit']         = 'admin/contacts/bulk_edit';
    $route['admin/contacts/export']            = 'admin/contacts/export';
    $route['admin/contacts/(:num)']            = 'admin/contacts/view/$1';
    $route['admin/contacts/(:num)/edit']       = 'admin/contacts/edit/$1';
    $route['admin/campaigns']                  = 'admin/campaigns/index';
    $route['google-posts'] = 'admin/gbp_posts/index';
    $route['google-posts/save'] = 'admin/gbp_posts/save';
    $route['google-posts/(:num)/publish'] = 'admin/gbp_posts/publish/$1';
    $route['google-posts/(:num)/delete'] = 'admin/gbp_posts/delete/$1';
    $route['reviews'] = 'admin/reviews/index';
    $route['reviews/connect'] = 'admin/reviews/connect';
    $route['reviews/google/callback'] = 'admin/reviews/callback';
    $route['reviews/disconnect'] = 'admin/reviews/disconnect';
    $route['reviews/locations'] = 'admin/reviews/locations';
    $route['reviews/credentials'] = 'admin/reviews/credentials';
    $route['reviews/check'] = 'admin/reviews/check';
    $route['reviews/ask'] = 'admin/reviews/ask';
    $route['reviews/ask-bulk'] = 'admin/reviews/ask_bulk';
    $route['reviews/(:num)/reply'] = 'admin/reviews/reply/$1';
    $route['reviews/(:num)/done'] = 'admin/reviews/done/$1';
    $route['ymochat/app-manifest'] = 'api/ymochat/manifest';
    $route['admin/google-posts'] = 'admin/gbp_posts/index';
    $route['admin/google-posts/save'] = 'admin/gbp_posts/save';
    $route['admin/google-posts/(:num)/publish'] = 'admin/gbp_posts/publish/$1';
    $route['admin/google-posts/(:num)/delete'] = 'admin/gbp_posts/delete/$1';
    $route['admin/reviews'] = 'admin/reviews/index';
    $route['admin/reviews/connect'] = 'admin/reviews/connect';
    $route['admin/reviews/google/callback'] = 'admin/reviews/callback';
    $route['admin/reviews/disconnect'] = 'admin/reviews/disconnect';
    $route['admin/reviews/locations'] = 'admin/reviews/locations';
    $route['admin/reviews/credentials'] = 'admin/reviews/credentials';
    $route['admin/reviews/check'] = 'admin/reviews/check';
    $route['admin/reviews/ask'] = 'admin/reviews/ask';
    $route['admin/reviews/ask-bulk'] = 'admin/reviews/ask_bulk';
    $route['admin/reviews/(:num)/reply'] = 'admin/reviews/reply/$1';
    $route['admin/reviews/(:num)/done'] = 'admin/reviews/done/$1';
    $route['admin/ymochat/app-manifest'] = 'api/ymochat/manifest';
    $route['admin/ymochat/push/key'] = 'admin/ymochat/push_key';
    $route['admin/ymochat/push/subscribe'] = 'admin/ymochat/push_subscribe';
    $route['admin/ymochat/push/state'] = 'admin/ymochat/push_state';
    $route['admin/ymochat/push/unsubscribe'] = 'admin/ymochat/push_unsubscribe';
    $route['admin/ymochat/push/test'] = 'admin/ymochat/push_test';
    $route['admin/ymochat'] = 'admin/ymochat/index';
    $route['admin/ymochat/feed'] = 'admin/ymochat/feed';
    $route['admin/ymochat/pulse'] = 'admin/ymochat/pulse';
    $route['admin/ymochat/(:num)/send'] = 'admin/ymochat/send/$1';
    $route['admin/ymochat/(:num)'] = 'admin/ymochat/thread/$1';
    $route['admin/inbox'] = 'admin/inbox/index';
    $route['admin/inbox/unread'] = 'admin/inbox/unread';
    $route['admin/inbox/read-all'] = 'admin/inbox/read_all';
    $route['admin/inbox/(:num)/reply'] = 'admin/inbox/reply/$1';
    $route['admin/inbox/(:num)'] = 'admin/inbox/view/$1';
    $route['admin/campaigns/delete'] = 'admin/campaigns/bulk_delete';
    $route['admin/campaigns/new']              = 'admin/campaigns/create';
    $route['admin/campaigns/(:num)']           = 'admin/campaigns/view/$1';
    $route['admin/campaigns/(:num)/edit']      = 'admin/campaigns/edit/$1';
    $route['admin/campaigns/(:num)/test'] = 'admin/campaigns/send_test/$1';
    $route['admin/campaigns/(:num)/duplicate'] = 'admin/campaigns/duplicate/$1';
    $route['admin/campaigns/(:num)/retry'] = 'admin/campaigns/retry/$1';
    $route['admin/campaigns/(:num)/no-whatsapp'] = 'admin/campaigns/no_whatsapp/$1';
    $route['admin/campaigns/(:num)/export'] = 'admin/campaigns/export/$1';
    $route['admin/campaigns/(:num)/send']      = 'admin/campaigns/send/$1';
    $route['admin/campaigns/(:num)/schedule']  = 'admin/campaigns/schedule/$1';
    $route['admin/whatsapp-templates'] = 'admin/wa_templates/index';
    $route['admin/whatsapp-templates/sync'] = 'admin/wa_templates/sync';
    $route['admin/whatsapp-templates/submit'] = 'admin/wa_templates/submit';
    $route['admin/recruitment']                = 'admin/recruitment/index';
    $route['admin/recruitment/new']            = 'admin/recruitment/create';
    $route['admin/recruitment/(:num)']         = 'admin/recruitment/view/$1';
    $route['admin/recruitment/(:num)/edit']    = 'admin/recruitment/edit/$1';
    $route['admin/recruitment/(:num)/upload']  = 'admin/recruitment/upload_resume/$1';
    $route['admin/recruitment/document/(:num)'] = 'admin/recruitment/document/$1';
    $route['admin/recruitment/(:num)/schedule']= 'admin/recruitment/schedule_interview/$1';
    $route['admin/reports']                    = 'admin/reports/index';
    $route['admin/reports/export/leads']       = 'admin/reports/export_leads';
    $route['admin/reports/export/service-due'] = 'admin/reports/export_service_due';
    $route['admin/reports/export/revenue']     = 'admin/reports/export_revenue';

} elseif (ymo_is_marketing_host_request()) {

    // -------------------------------------------------------------------
    // MARKETING HOST — www.yourmechaniconline.com (booking/admin unchanged)
    // -------------------------------------------------------------------
    // CI3 default_controller cannot resolve subdirectory/controller (it treats
    // "marketing/home" as class Marketing + method home). Explicit empty-URI
    // route is required for the homepage.
    $route['404_override']         = 'marketing/legacy/go';
    $route['default_controller']   = 'marketing/home/index';
    $route['sitemap.xml']          = 'marketing/seo/sitemap';
    $route['robots.txt']           = 'marketing/seo/robots';
    $route['llms.txt']             = 'marketing/seo/llms';
    $route['geo/city']             = 'marketing/geo/city';
    $route['contact-us']           = 'marketing/contact/index';
    $route['request-callback']        = 'marketing/contact/callback';
    $route['review/([a-z]+)']        = 'marketing/review_link/go/$1';
    $route['review']                 = 'marketing/review_link/go';
    $route['media/gbp/(:num)/([a-f0-9]+)'] = 'marketing/gbp_media/show/$1/$2';
    $route['request-callback/thanks'] = 'marketing/contact/callback_thanks';
    $route['about-us']             = 'marketing/pages/show';
    $route['api/offers/active']    = 'api/offers/active';
    $route['api/ymochat/beat']     = 'api/ymochat/beat';
    $route['api/ads/wa-click']     = 'api/ads/wa_click';
    $route['api/ymochat/send']     = 'api/ymochat/send';
    $route['api/ymochat/contact']  = 'api/ymochat/contact';
    $route['api/offers/preview/(:num)'] = 'api/offers/preview/$1';

    $marketing_pages_file = APPPATH.'config/marketing_pages_data.php';
    if (is_file($marketing_pages_file)) {
        // Helpers are not autoloaded yet when routes.php runs — load them so brand/SEO
        // slugs are registered (marketing_pages_data merges via marketing_* functions).
        foreach (array('marketing_helper', 'marketing_seo_growth_helper', 'marketing_seo_enhancements_helper', 'marketing_internal_links_helper', 'marketing_seo_project_helper') as $helper) {
            $helper_file = APPPATH.'helpers/'.$helper.'.php';
            if (is_file($helper_file)) {
                require_once $helper_file;
            }
        }

        $marketing_pages = require $marketing_pages_file;
        if (is_array($marketing_pages)) {
            foreach (array_keys($marketing_pages) as $slug) {
                if ($slug === 'about-us') {
                    continue;
                }
                $route[$slug] = 'marketing/pages/show';
            }
        }
    }

    // Legacy WP URLs (tag/*, category/*, old nested paths) → 301 via redirect map.
    // Must be last — CI3 cannot use subdirectory 404_override reliably.
    $route['(.+)'] = 'marketing/legacy/go';

} else {

    // -------------------------------------------------------------------
    // PUBLIC / BOOKING HOST (default behaviour)
    // -------------------------------------------------------------------
    $route['default_controller'] = 'home';

    // Customer auth
    $route['signup'] = 'auth/signup';
    $route['signup/verify'] = 'auth/verify_otp';
    $route['signup/resend'] = 'auth/resend_otp';
    $route['login'] = 'auth/login';
    $route['logout'] = 'auth/logout';
    $route['forgot-password'] = 'auth/forgot';
    $route['reset-password'] = 'auth/reset';
    $route['reset-password/resend'] = 'auth/resend_reset_otp';

    // Public marketing-ish pages
    $route['packages'] = 'bookings/packages';
    $route['quick-book'] = 'quick_book/index';
    $route['quick-book/thanks'] = 'quick_book/thanks';

    // Booking flow
    $route['book/(:any)'] = 'bookings/start/$1';
    $route['booking/vehicle'] = 'bookings/vehicle';
    $route['booking/details'] = 'bookings/details';
    $route['booking/confirm'] = 'bookings/confirm';
    $route['booking/place'] = 'bookings/place';
    $route['booking/success/(:num)'] = 'bookings/success/$1';
    $route['booking/rebook/(:num)'] = 'bookings/rebook/$1';

    // Account area
    $route['account'] = 'account/index';
    $route['account/profile'] = 'account/profile';
    $route['account/bookings'] = 'account/bookings';
    $route['account/bookings/(:num)/invoice/(:num)/pdf'] = 'account/download_invoice/$1/$2';
    $route['account/bookings/(:num)'] = 'account/booking_view/$1';

    // Vehicle management
    $route['vehicles'] = 'vehicles/index';
    $route['vehicles/new'] = 'vehicles/create';
    $route['vehicles/(:num)/edit'] = 'vehicles/edit/$1';
    $route['vehicles/(:num)/delete'] = 'vehicles/delete/$1';

    // Admin legacy /admin/* URLs (prefer admin subdomain in production —
    // the pre_controller hook 301-canonicalizes when YMO_ADMIN_APP_URL set).
    $route['admin'] = 'admin/dashboard';
    $route['admin/login'] = 'admin/auth/login';
    $route['admin/logout'] = 'admin/auth/logout';
    $route['admin/dashboard'] = 'admin/dashboard';
    $route['admin/bookings'] = 'admin/bookings/index';
    $route['admin/bookings/new'] = 'admin/bookings/create';
    $route['admin/bookings/lookup'] = 'admin/bookings/lookup';
    $route['admin/bookings/(:num)'] = 'admin/bookings/view/$1';
    $route['admin/job-cards'] = 'admin/work_orders/index';
    $route['admin/job-cards/new'] = 'admin/work_orders/create';
    $route['admin/job-cards/catalog-search'] = 'admin/work_orders/catalog_search';
    $route['admin/job-cards/(:num)/update'] = 'admin/work_orders/update/$1';
    $route['admin/job-cards/(:num)/status'] = 'admin/work_orders/status/$1';
    $route['admin/job-cards/(:num)/delete'] = 'admin/work_orders/delete/$1';
    $route['admin/job-cards/(:num)/print'] = 'admin/work_orders/print_card/$1';
    $route['admin/job-cards/(:num)/lines/add'] = 'admin/work_orders/line_add/$1';
    $route['admin/job-cards/(:num)/lines/(:num)/update'] = 'admin/work_orders/line_update/$1/$2';
    $route['admin/job-cards/(:num)/lines/(:num)/delete'] = 'admin/work_orders/line_delete/$1/$2';
    $route['admin/job-cards/(:num)/lines/(:num)/done'] = 'admin/work_orders/line_done/$1/$2';
    $route['admin/job-cards/(:num)/note'] = 'admin/work_orders/note/$1';
    $route['admin/job-cards/(:num)/photos'] = 'admin/work_orders/photo_add/$1';
    $route['admin/job-cards/(:num)/photos/(:num)/delete'] = 'admin/work_orders/photo_delete/$1/$2';
    $route['admin/job-cards/photo/(:num)'] = 'admin/work_orders/photo/$1';
    $route['admin/technicians'] = 'admin/technicians/index';
    $route['admin/technicians/new'] = 'admin/technicians/create';
    $route['admin/technicians/(:num)/edit'] = 'admin/technicians/edit/$1';
    $route['admin/technicians/(:num)/signout'] = 'admin/technicians/signout/$1';
    $route['admin/tech'] = 'tech/index';
    $route['admin/tech/sw.js'] = 'tech/sw';
    $route['admin/tech/manifest.webmanifest'] = 'tech/manifest';
    $route['admin/tech/icon.svg'] = 'tech/icon';
    $route['admin/tech/photo/(:num)'] = 'tech/photo/$1';
    $route['admin/tech/api/login'] = 'tech/api_login';
    $route['admin/tech/api/logout'] = 'tech/api_logout';
    $route['admin/tech/api/me'] = 'tech/api_me';
    $route['admin/tech/api/lang'] = 'tech/api_lang';
    $route['admin/tech/api/jobs'] = 'tech/api_jobs';
    $route['admin/tech/api/jobs/(:num)'] = 'tech/api_job/$1';
    $route['admin/tech/api/jobs/(:num)/lines/(:num)'] = 'tech/api_line/$1/$2';
    $route['admin/tech/api/jobs/(:num)/status'] = 'tech/api_status/$1';
    $route['admin/tech/api/jobs/(:num)/note'] = 'tech/api_note/$1';
    $route['admin/tech/api/jobs/(:num)/photos'] = 'tech/api_photo/$1';
    $route['admin/job-cards/(:num)'] = 'admin/work_orders/view/$1';
    $route['admin/bookings/(:num)/status'] = 'admin/bookings/update_status/$1';
    $route['admin/bookings/(:num)/send-review'] = 'admin/bookings/send_review/$1';
    $route['admin/bookings/(:num)/invoice'] = 'admin/bookings/create_invoice/$1';
    $route['admin/bookings/(:num)/invoice/(:num)/edit'] = 'admin/bookings/edit_invoice/$1/$2';
    $route['admin/bookings/(:num)/invoice/(:num)/pdf'] = 'admin/bookings/download_invoice/$1/$2';
    $route['admin/bookings/(:num)/whatsapp'] = 'admin/bookings/whatsapp_update/$1';
    $route['admin/bookings/(:num)/whatsapp/media/(:num)'] = 'admin/bookings/whatsapp_media/$1/$2';
    $route['admin/online-accounts'] = 'admin/online_accounts/index';
    $route['admin/online-accounts/(:num)'] = 'admin/online_accounts/view/$1';
    $route['admin/customers'] = 'admin/contacts/index';
    $route['admin/customers/new'] = 'admin/contacts/create';
    $route['admin/customers/import'] = 'admin/contacts/import';
    $route['admin/customers/import/template'] = 'admin/contacts/import_template';
    $route['admin/customers/import/preview'] = 'admin/contacts/import_preview';
    $route['admin/customers/import/commit'] = 'admin/contacts/import_commit';
    $route['admin/customers/bulk-edit'] = 'admin/contacts/bulk_edit';
    $route['admin/customers/export'] = 'admin/contacts/export';
    $route['admin/customers/(:num)/link-user'] = 'admin/contacts/link_user/$1';
    $route['admin/customers/(:num)/edit'] = 'admin/contacts/edit/$1';
    $route['admin/customers/(:num)'] = 'admin/contacts/view/$1';
    $route['admin/packages'] = 'admin/packages/index';
    $route['admin/packages/new'] = 'admin/packages/create';
    $route['admin/packages/(:num)/edit'] = 'admin/packages/edit/$1';
    $route['admin/packages/(:num)/delete'] = 'admin/packages/delete/$1';
    $route['admin/catalogue'] = 'admin/catalogue/index';
    $route['admin/catalogue/new'] = 'admin/catalogue/create';
    $route['admin/catalogue/(:num)/edit'] = 'admin/catalogue/edit/$1';
    $route['admin/catalogue/(:num)/toggle'] = 'admin/catalogue/toggle/$1';
    $route['admin/offers'] = 'admin/offers/index';
    $route['admin/offers/new'] = 'admin/offers/create';
    $route['admin/offers/(:num)/edit'] = 'admin/offers/edit/$1';
    $route['admin/offers/(:num)/delete'] = 'admin/offers/delete/$1';
    $route['admin/settings'] = 'admin/settings/index';
    $route['admin/team'] = 'admin/team/index';
    $route['admin/team/new'] = 'admin/team/create';
    $route['admin/team/(:num)/edit'] = 'admin/team/edit/$1';
    $route['admin/team/(:num)/reset-password'] = 'admin/team/reset_password/$1';
    $route['admin/team/(:num)/deactivate'] = 'admin/team/deactivate/$1';
    $route['admin/team/(:num)/activate'] = 'admin/team/activate/$1';
    $route['admin/roles'] = 'admin/roles/index';
    $route['admin/roles/new'] = 'admin/roles/create';
    $route['admin/roles/(:num)/edit'] = 'admin/roles/edit/$1';
    $route['admin/roles/(:num)/delete'] = 'admin/roles/delete/$1';
    $route['admin/leads'] = 'admin/leads/index';
    $route['admin/leads/pipeline'] = 'admin/leads/pipeline';
    $route['admin/leads/new'] = 'admin/leads/create';
    $route['admin/leads/(:num)'] = 'admin/leads/view/$1';
    $route['admin/leads/(:num)/edit'] = 'admin/leads/edit/$1';
    $route['admin/leads/(:num)/assign'] = 'admin/leads/assign/$1';
    $route['admin/leads/(:num)/activity'] = 'admin/leads/add_activity/$1';
    $route['admin/leads/(:num)/send-chat'] = 'admin/leads/send_chat/$1';
    $route['admin/leads/(:num)/log'] = 'admin/leads/log_call/$1';
    $route['admin/leads/(:num)/stage'] = 'admin/leads/update_stage/$1';
    $route['admin/leads/(:num)/archive'] = 'admin/leads/archive/$1';
    $route['admin/leads/(:num)/convert'] = 'admin/leads/convert/$1';
    $route['admin/tasks'] = 'admin/tasks/index';
    $route['admin/tasks/new'] = 'admin/tasks/create';
    $route['admin/tasks/(:num)/edit'] = 'admin/tasks/edit/$1';
    $route['admin/tasks/(:num)/snooze'] = 'admin/tasks/snooze/$1';
    $route['admin/tasks/(:num)/done'] = 'admin/tasks/done/$1';
    $route['admin/tasks/(:num)/skip'] = 'admin/tasks/skip/$1';
    $route['admin/contacts'] = 'admin/contacts/index';
    $route['admin/contacts/new'] = 'admin/contacts/create';
    $route['admin/contacts/import'] = 'admin/contacts/import';
    $route['admin/contacts/import/template'] = 'admin/contacts/import_template';
    $route['admin/contacts/import/preview'] = 'admin/contacts/import_preview';
    $route['admin/contacts/import/commit'] = 'admin/contacts/import_commit';
    $route['admin/contacts/bulk-edit'] = 'admin/contacts/bulk_edit';
    $route['admin/contacts/export'] = 'admin/contacts/export';
    $route['admin/contacts/(:num)'] = 'admin/contacts/view/$1';
    $route['admin/contacts/(:num)/edit'] = 'admin/contacts/edit/$1';
    $route['admin/campaigns'] = 'admin/campaigns/index';
    $route['admin/google-posts'] = 'admin/gbp_posts/index';
    $route['admin/google-posts/save'] = 'admin/gbp_posts/save';
    $route['admin/google-posts/(:num)/publish'] = 'admin/gbp_posts/publish/$1';
    $route['admin/google-posts/(:num)/delete'] = 'admin/gbp_posts/delete/$1';
    $route['admin/reviews'] = 'admin/reviews/index';
    $route['admin/reviews/connect'] = 'admin/reviews/connect';
    $route['admin/reviews/google/callback'] = 'admin/reviews/callback';
    $route['admin/reviews/disconnect'] = 'admin/reviews/disconnect';
    $route['admin/reviews/locations'] = 'admin/reviews/locations';
    $route['admin/reviews/credentials'] = 'admin/reviews/credentials';
    $route['admin/reviews/check'] = 'admin/reviews/check';
    $route['admin/reviews/ask'] = 'admin/reviews/ask';
    $route['admin/reviews/ask-bulk'] = 'admin/reviews/ask_bulk';
    $route['admin/reviews/(:num)/reply'] = 'admin/reviews/reply/$1';
    $route['admin/reviews/(:num)/done'] = 'admin/reviews/done/$1';
    $route['admin/ymochat/app-manifest'] = 'api/ymochat/manifest';
    $route['admin/ymochat/push/key'] = 'admin/ymochat/push_key';
    $route['admin/ymochat/push/subscribe'] = 'admin/ymochat/push_subscribe';
    $route['admin/ymochat/push/state'] = 'admin/ymochat/push_state';
    $route['admin/ymochat/push/unsubscribe'] = 'admin/ymochat/push_unsubscribe';
    $route['admin/ymochat/push/test'] = 'admin/ymochat/push_test';
    $route['admin/ymochat'] = 'admin/ymochat/index';
    $route['admin/ymochat/feed'] = 'admin/ymochat/feed';
    $route['admin/ymochat/pulse'] = 'admin/ymochat/pulse';
    $route['admin/ymochat/(:num)/send'] = 'admin/ymochat/send/$1';
    $route['admin/ymochat/(:num)'] = 'admin/ymochat/thread/$1';
    $route['admin/inbox'] = 'admin/inbox/index';
    $route['admin/inbox/unread'] = 'admin/inbox/unread';
    $route['admin/inbox/read-all'] = 'admin/inbox/read_all';
    $route['admin/inbox/(:num)/reply'] = 'admin/inbox/reply/$1';
    $route['admin/inbox/(:num)'] = 'admin/inbox/view/$1';
    $route['admin/campaigns/delete'] = 'admin/campaigns/bulk_delete';
    $route['admin/campaigns/new'] = 'admin/campaigns/create';
    $route['admin/campaigns/(:num)'] = 'admin/campaigns/view/$1';
    $route['admin/campaigns/(:num)/edit'] = 'admin/campaigns/edit/$1';
    $route['admin/campaigns/(:num)/test'] = 'admin/campaigns/send_test/$1';
    $route['admin/campaigns/(:num)/duplicate'] = 'admin/campaigns/duplicate/$1';
    $route['admin/campaigns/(:num)/retry'] = 'admin/campaigns/retry/$1';
    $route['admin/campaigns/(:num)/no-whatsapp'] = 'admin/campaigns/no_whatsapp/$1';
    $route['admin/campaigns/(:num)/export'] = 'admin/campaigns/export/$1';
    $route['admin/campaigns/(:num)/send'] = 'admin/campaigns/send/$1';
    $route['admin/campaigns/(:num)/schedule'] = 'admin/campaigns/schedule/$1';
    $route['admin/whatsapp-templates'] = 'admin/wa_templates/index';
    $route['admin/whatsapp-templates/sync'] = 'admin/wa_templates/sync';
    $route['admin/whatsapp-templates/submit'] = 'admin/wa_templates/submit';
    $route['admin/recruitment'] = 'admin/recruitment/index';
    $route['admin/recruitment/new'] = 'admin/recruitment/create';
    $route['admin/recruitment/(:num)'] = 'admin/recruitment/view/$1';
    $route['admin/recruitment/(:num)/edit'] = 'admin/recruitment/edit/$1';
    $route['admin/recruitment/(:num)/upload'] = 'admin/recruitment/upload_resume/$1';
    $route['admin/recruitment/document/(:num)'] = 'admin/recruitment/document/$1';
    $route['admin/recruitment/(:num)/schedule'] = 'admin/recruitment/schedule_interview/$1';
    $route['admin/reports'] = 'admin/reports/index';
    $route['admin/reports/export/leads'] = 'admin/reports/export_leads';
    $route['admin/reports/export/service-due'] = 'admin/reports/export_service_due';
    $route['admin/reports/export/revenue'] = 'admin/reports/export_revenue';
    $route['api/webhooks/meta'] = 'api/webhooks/meta';
    $route['api/webhooks/website'] = 'api/webhooks/website';
    $route['api/webhooks/whatsapp'] = 'api/webhooks/whatsapp';
    $route['api/ads/wa-click'] = 'api/ads/wa_click';
    $route['api/google-ads/conversions\.csv'] = 'api/ads/conversions';
    $route['api/google-ads/(qualified|booked)\.csv'] = 'api/ads/conversions/$1';
    $route['api/offers/active'] = 'api/offers/active';
    $route['api/offers/preview/(:num)'] = 'api/offers/preview/$1';
}
