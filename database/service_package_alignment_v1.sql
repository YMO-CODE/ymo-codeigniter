-- Align the live Basic Service package with the advertised ₹2,499 offer.
-- Safe to re-run: features are replaced for the package selected by slug.
START TRANSACTION;

SET @basic_service_id := (
    SELECT `id`
    FROM `service_packages`
    WHERE `slug` = 'basic-service'
    LIMIT 1
);

UPDATE `service_packages`
SET
    `name` = 'Basic Service',
    `summary` = 'Periodic maintenance with oil and filter change, essential checks, wash, and vacuum.',
    `price` = 2499.00
WHERE `id` = @basic_service_id;

DELETE FROM `service_package_features`
WHERE `package_id` = @basic_service_id;

INSERT INTO `service_package_features` (`package_id`, `feature_text`, `sort_order`)
SELECT @basic_service_id, feature_text, sort_order
FROM (
    SELECT 'Engine oil & filter change' AS feature_text, 10 AS sort_order
    UNION ALL SELECT 'Air and AC filter cleaning', 20
    UNION ALL SELECT 'Front and rear brake cleaning', 30
    UNION ALL SELECT 'Coolant, brake fluid & battery water top-up', 40
    UNION ALL SELECT 'Exterior wash + interior vacuum', 50
    UNION ALL SELECT '15-point safety inspection', 60
) AS aligned_features
WHERE @basic_service_id IS NOT NULL;

COMMIT;
