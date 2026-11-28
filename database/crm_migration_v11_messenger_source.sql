-- CRM v11: Facebook Messenger chats get their own lead source
-- (they were filed under "Meta Ads (Facebook)").
INSERT IGNORE INTO `crm_lead_sources` (`slug`, `label`) VALUES ('messenger', 'Facebook Messenger');

-- Move existing Messenger chat leads to it.
UPDATE `crm_leads` l
  JOIN `crm_lead_sources` s ON s.slug = 'messenger'
   SET l.source_id = s.id, l.updated_at = l.updated_at
 WHERE l.external_provider = 'messenger_dm';
