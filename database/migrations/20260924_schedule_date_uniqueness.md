# Schedule uniqueness migration

The old database allows only one schedule per campaign/area, while the application allows up to two non-cancelled schedules on distinct dates. The SQL file adds campaign/area/date uniqueness before removing the obsolete campaign/area unique index. Existing rows are unchanged. Application transactions continue enforcing the two-schedule limit.

Run on the selected application database. DDL auto-commits: if interrupted, rerun the same file. Deploy the existing date-aware schedule controller/model before applying.

Rollback is possible only while no campaign/area pair has multiple rows:

```sql
SELECT campaign_id, postal_area_id, COUNT(*)
FROM area_collection_schedules
GROUP BY campaign_id, postal_area_id HAVING COUNT(*) > 1;
```

If that query returns any rows, do not roll back or delete records automatically. Otherwise add the old unique index first, then remove the new one:

```sql
ALTER TABLE area_collection_schedules
  ADD UNIQUE KEY uq_schedules_campaign_area (campaign_id, postal_area_id);
ALTER TABLE area_collection_schedules DROP INDEX uq_schedules_campaign_area_date;
```
