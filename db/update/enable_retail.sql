SET NAMES 'utf8mb4';

-- Увімкнення документа "Касовий чек" (роздрібна торгівля).
-- За замовчуванням у dist-схемі рядок  disabled=1 (див. db/db.sql).
UPDATE metadata SET disabled=0 WHERE meta_name='POSCheck';
