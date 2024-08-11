import { test, expect } from '@playwright/test';
test.setTimeout(120000)
test('All pages OK', async ({ page }) => {
  await page.goto('http://local.zstore/');
  await page.getByLabel('Логін').click();
  await page.getByLabel('Логін').fill('admin');
  await page.getByLabel('Пароль').click();
  await page.getByLabel('Пароль').fill('admin');
  await page.getByRole('button', { name: 'Увійти' }).click();
  await page.getByRole('link', { name: '' }).click();
 
  
  await page.goto('/index.php');
  await page.getByRole('link', { name: '' }).click();
 

  await page.goto('/index.php?p=App/Pages/SystemLog');
  await page.goto('/index.php?p=App/Pages/CronTab');
  await page.goto('/index.php?p=App/Pages/Subscribes');
  await page.goto('/index.php?p=App/Pages/PosList');
  await page.goto('/index.php?p=App/Pages/Users');
  await page.goto('/index.php?p=App/Pages/Roles');
  await page.goto('/index.php?p=App/Pages/BranchList');
  await page.goto('/index.php?p=App/Pages/FirmList');
  await page.goto('/index.php?p=App/Pages/MenuList');
  await page.goto('/index.php?p=App/Pages/Options');
  await page.goto('/index.php?p=App/Pages/NotifyList');
  await page.goto('/index.php?p=App/Pages/Jobs');
  await page.goto('/index.php?p=App/Pages/EmpAcc');
  await page.goto('/index.php?p=App/Pages/UserProfile');

  await page.goto('/index.php?p=App/Pages/Doc/GoodsReceipt');
  await page.goto('/index.php?p=App/Pages/Doc/GoodsIssue');
  await page.goto('/index.php?p=App/Pages/Doc/Warranty');
  await page.goto('/index.php?p=App/Pages/Doc/ServiceAct');
  await page.goto('/index.php?p=App/Pages/Doc/ReturnIssue');
  await page.goto('/index.php?p=App/Pages/Doc/Task');
  await page.goto('/index.php?p=App/Pages/Doc/Order');
  await page.goto('/index.php?p=App/Pages/Doc/ProdReceipt');
  await page.goto('/index.php?p=App/Pages/Doc/ProdIssue');
  await page.goto('/index.php?p=App/Pages/Doc/OrderCust');
  await page.goto('/index.php?p=App/Pages/Doc/RetCustIssue');
  await page.goto('/index.php?p=App/Pages/Doc/TransItem');
  await page.goto('/index.php?p=App/Pages/Doc/IncomeMoney');
  await page.goto('/index.php?p=App/Pages/Doc/OutcomeMoney');
  await page.goto('/index.php?p=App/Pages/Doc/Inventory');
  await page.goto('/index.php?p=App/Pages/Doc/InvoiceCust');
  await page.goto('/index.php?p=App/Pages/Doc/Invoice');
  await page.goto('/index.php?p=App/Pages/Doc/POSCheck');
  await page.goto('/index.php?p=App/Pages/Doc/OutcomeItem');
  await page.goto('/index.php?p=App/Pages/Doc/IncomeItem');
  await page.goto('/index.php?p=App/Pages/Doc/OutSalary');
  await page.goto('/index.php?p=App/Pages/Doc/MoveItem');
  await page.goto('/index.php?p=App/Pages/Doc/TTN');
  await page.goto('/index.php?p=App/Pages/Doc/MoveMoney');
  await page.goto('/index.php?p=App/Pages/Doc/OrderFood');
  await page.goto('/index.php?p=App/Pages/Doc/CalcSalary');
  await page.goto('/index.php?p=App/Pages/Doc/MovePart');
  await page.goto('/index.php?p=App/Pages/Doc/IncomeService');
  await page.goto('/index.php?p=App/Pages/Doc/OfficeDoc');
  await page.goto('/index.php?p=App/Pages/Doc/ProdReturn');


  
  await page.goto('/index.php?p=App/Pages/Report/ItemActivity');
  await page.goto('/index.php?p=App/Pages/Report/ABC');
  await page.goto('/index.php?p=App/Pages/Report/EmpTask');
  await page.goto('/index.php?p=App/Pages/Report/EmpTask');
  await page.goto('/index.php?p=App/Pages/Report/Income');
  await page.goto('/index.php?p=App/Pages/Report/Outcome');
  await page.goto('/index.php?p=App/Pages/Report/Prod');
  await page.goto('/index.php?p=App/Pages/Report/Price');
  await page.goto('/index.php?p=App/Pages/Report/PayActivity');
  await page.goto('/index.php?p=App/Pages/Report/PayBalance');
  await page.goto('/index.php?p=App/Pages/Report/CustOrder');
  await page.goto('/index.php?p=App/Pages/Report/SalaryRep');
  await page.goto('/index.php?p=App/Pages/Report/Timestat');
  await page.goto('/index.php?p=App/Pages/Report/NoLiq');
  await page.goto('/index.php?p=App/Pages/Report/ItemOrder');
  await page.goto('/index.php?p=App/Pages/Report/Returnselled');
  await page.goto('/index.php?p=App/Pages/Report/Returnbayed');
  await page.goto('/index.php?p=App/Pages/Report/StoreItems');
  await page.goto('/index.php?p=App/Pages/Report/CompareAct');
  await page.goto('/index.php?p=App/Pages/Report/Reserved');
  await page.goto('/index.php?p=App/Pages/Report/OLAP');
  await page.goto('/index.php?p=App/Pages/Report/OutFood');
  await page.goto('/index.php?p=App/Pages/Report/Balance');
  await page.goto('/index.php?p=App/Pages/Report/PredSell');
  await page.goto('/index.php?p=App/Pages/Report/ItemComission');
 

  await page.goto('/index.php?p=App/Pages/Register/DocList');
  await page.goto('/index.php?p=App/Pages/Register/TaskList');
  await page.goto('/index.php?p=App/Pages/Register/OrderList');
  await page.goto('/index.php?p=App/Pages/Register/GIList');
  await page.goto('/index.php?p=App/Pages/Register/GRList');
  await page.goto('/index.php?p=App/Pages/Register/OrderCustList');
  await page.goto('/index.php?p=App/Pages/Register/PayList');
  await page.goto('/index.php?p=App/Pages/Register/StockList');
  await page.goto('/index.php?p=App/Pages/Register/SerList');
  await page.goto('/index.php?p=App/Pages/Register/ItemList');
  await page.goto('/index.php?p=App/Pages/Register/PaySelList');
  await page.goto('/index.php?p=App/Pages/Register/PayBayList');
  await page.goto('/index.php?p=App/Pages/Register/DeliveryList');
  await page.goto('/index.php?p=App/Pages/Register/IOState');
  await page.goto('/index.php?p=App/Pages/Register/ProdProcList');
  await page.goto('/index.php?p=App/Pages/Register/ProdStageList');
  await page.goto('/index.php?p=App/Pages/Register/CustItems');
  await page.goto('/index.php?p=App/Pages/Register/PayTable');
  await page.goto('/index.php?p=App/Pages/Register/OfficeList');
  
  await page.goto('/index.php?p=App/Pages/Reference/StoreList');
  await page.goto('/index.php?p=App/Pages/Reference/EmployeeList');
  await page.goto('/index.php?p=App/Pages/Reference/CategoryList');
  await page.goto('/index.php?p=App/Pages/Reference/CustomerList');
  await page.goto('/index.php?p=App/Pages/Reference/ServiceList');
  await page.goto('/index.php?p=App/Pages/Reference/ProdAreaList');
  await page.goto('/index.php?p=App/Pages/Reference/EqList');
  await page.goto('/index.php?p=App/Pages/Reference/MFList');
  await page.goto('/index.php?p=App/Pages/Reference/ContractList');
  await page.goto('/index.php?p=App/Pages/Reference/SalaryTypeList');
  
 
  
  await page.goto('/index.php?p=App/Pages/Service/Import');
  await page.goto('/index.php?p=App/Pages/Service/ARMPos');
  await page.goto('/index.php?p=App/Pages/Service/Export');
  await page.goto('/index.php?p=App/Pages/Service/ARMFood');
  await page.goto('/index.php?p=App/Pages/Service/ArmProdFood');
  await page.goto('/index.php?p=App/Pages/Service/Discounts');
  
  
  await page.goto('/shop');
  await page.goto('/store');

  await page.getByRole('link', { name: '' }).click();
  
  await page.getByRole('link', { name: ' Сидоров' }).click();
  await page.getByRole('link', { name: ' Вийти' }).click();
});