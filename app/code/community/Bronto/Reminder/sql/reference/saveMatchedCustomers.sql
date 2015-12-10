SET @ruleId = 1;

SELECT      `root`.`entity_id`, `c`.`coupon_id`
FROM        `customer_entity` AS `root`
LEFT JOIN   `bronto_reminder_rule_coupon` AS `c`
    ON c.customer_id = root.entity_id
    AND c.rule_id = @ruleId
WHERE (website_id='1')
AND ((
    IFNULL((SELECT 1 FROM `sales_flat_quote` AS `quote`
            INNER JOIN `core_store` AS `store`
                ON quote.store_id = store.store_id
            WHERE (store.website_id='1')
            AND (UNIX_TIMESTAMP('2012-01-20 21:13:09' - INTERVAL 2 HOUR) < UNIX_TIMESTAMP(quote.updated_at))
            AND (UNIX_TIMESTAMP('2012-01-20 21:13:09' - INTERVAL 1 HOUR) > UNIX_TIMESTAMP(quote.updated_at))
            AND (quote.is_active = 1)
            AND (quote.items_count > 0)
            AND (quote.customer_id = root.entity_id)
            LIMIT 1),
    0) = 1))
AND (c.emails_failed IS NULL OR c.emails_failed < 1 );