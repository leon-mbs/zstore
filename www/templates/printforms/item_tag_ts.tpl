SIZE 300,200
CLS       
BOX 3,3,294,194,2
TEXT 10,10,"2",0,1,1,"{{name}}"
TEXT 10,50,"3",0,1,1,"{{article}}"
TEXT 200,50,"3",0,1,1,"{{price}}"
{{#isbarcode}}
BARCODE 20,100,"128",72,1,0,2,4,"{{barcode}}"
{{/isbarcode}}
PRINT 1,1


