CREATE
VIEW documents_view
AS
SELECT
  d.document_id AS document_id,
  d.document_number AS document_number,
  d.document_date AS document_date,
  d.user_id AS user_id,
  d.content AS content,
  d.amount AS amount,
  d.meta_id AS meta_id,
  u.username AS username,
  c.customer_id AS customer_id,
  c.customer_name AS customer_name,
  d.state AS state,
  d.notes AS notes,
  d.payamount AS payamount,
  (SELECT COALESCE(SUM(amount),0)FROM paylist p WHERE  p.document_id = d.document_id)     AS `payed`,
  d.parent_id AS parent_id,
  d.branch_id AS branch_id,
  b.branch_name AS branch_name,
  d.firm_id AS firm_id,
  d.priority AS priority,
  f.firm_name AS firm_name,
  d.lastupdate AS lastupdate,
  metadata.meta_name AS meta_name,
  metadata.description AS meta_desc
FROM (((((documents d
  LEFT JOIN users_view u
    ON ((d.user_id = u.user_id)))
  LEFT JOIN customers c
    ON ((d.customer_id = c.customer_id)))
  JOIN metadata
    ON ((metadata.meta_id = d.meta_id)))
  LEFT JOIN branches b
    ON ((d.branch_id = b.branch_id)))
  LEFT JOIN firms f
    ON ((d.firm_id = f.firm_id))) ;