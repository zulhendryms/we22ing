SELECT p.Oid,p.Code,s.Code, p.TotalAmount, SUM(IFNULL(j.DebetAmount,0)), SUM(IFNULL(j.DebetBase,0))
  FROM trdpurchaseinvoice p
  LEFT OUTER JOIN sysstatus s ON p.Status = s.Oid 
  LEFT OUTER JOIN accjournal j ON p.Oid = j.PurchaseInvoice
  WHERE s.Code IN ('posted','complete')
  GROUP BY p.Oid, p.Status, s.Code, p.TotalAmount
HAVING p.TotalAmount != SUM(IFNULL(j.DebetAmount,0));

SELECT p.Oid,p.Code,s.Code, p.TotalAmount, SUM(IFNULL(j.DebetAmount,0)), SUM(IFNULL(j.DebetBase,0))
  FROM acccashbank p
  LEFT OUTER JOIN sysstatus s ON p.Status = s.Oid 
  LEFT OUTER JOIN accjournal j ON p.Oid = j.CashBank
  WHERE s.Code IN ('posted','complete')
  GROUP BY p.Oid, p.Status, s.Code, p.TotalAmount
HAVING p.TotalAmount != SUM(IFNULL(j.DebetAmount,0));