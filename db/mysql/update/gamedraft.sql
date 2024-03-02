 
 
INSERT INTO [dbo].[ContentType]
           (ContentTypeID
           ,ContentTypeName)
     VALUES
           (10 ,'Game')
           
GO 
INSERT INTO [dbo].[ApplicationPermission]
           ([ApplicationId]
           ,[PermissionName]
           ,[Permission]
           ,[Active])
     VALUES
           (2
           ,'Games'
           ,'View'
           ,1)
GO

INSERT INTO [dbo].[ApplicationPermission]
           ([ApplicationId]
           ,[PermissionName]
           ,[Permission]
           ,[Active])
     VALUES
           (2
           ,'Games'
           ,'Change'
           ,1)
GO 
CREATE TABLE [dbo].[Game](
    [GameId] [int] IDENTITY(1,1) NOT NULL,
    [Title] [nvarchar](255) NOT NULL,
    [Description] [nvarchar](1000) NULL,
    [FormulaId] [int] NULL,
    [StoredProcId] [int] NULL,
    [DateFrom] [datetime] NULL,
    [DateTo] [datetime] NULL,
    [ProgramID] [int] NULL,
    [Period] [smallint] NULL,
    [Rate1] [smallint] NOT NULL,
    [Rate2] [smallint] NOT NULL,
    [Rate3] [smallint] NOT NULL,
    [LastStart [datetime] NULL,
CONSTRAINT [PK_Game] PRIMARY KEY CLUSTERED 
(
    [GameId] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
GO


CREATE TABLE [dbo].[RoleToGame](
    [id] [int] IDENTITY(1,1) NOT NULL,
    [RoleId] [int] NOT NULL,
    [GameId] [int] NOT NULL,
    [Active] [bit] NOT NULL,
 CONSTRAINT [PK_RoleToGame] PRIMARY KEY CLUSTERED 
(
    [id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
GO 
 
 
 
    CREATE TABLE [dbo].[GameJob](
        Id [int] IDENTITY(1,1) NOT NULL,
        GameId [int]  NOT NULL,
        DateStart [DateTime]  NOT NULL,    
        DateStop [DateTime]  NOT NULL,    
        LastStart [DateTime]   NULL,    
        Active [bit]  NOT NULL,    
        Winner1 [int]   NULL,
        Winner2 [int]   NULL,
        Winner3 [int]   NULL,
     CONSTRAINT [PK_GameJob] PRIMARY KEY CLUSTERED 
    (
        [Id] ASC
    )WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
    ) ON [PRIMARY]
    GO      
   
 
 CREATE TABLE [dbo].[GameResult](
    Id [bigint] IDENTITY(1,1) NOT NULL,
    GameJobId [int], 
    EmployeeId [bigint]  NOT NULL,
    Score decimal(11,2) NOT NULL,  
     CONSTRAINT [PK_GameResult] PRIMARY KEY CLUSTERED 
    (
        [Id] ASC
    )WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
    ) ON [PRIMARY]
    GO  
 
   
 CREATE TABLE [dbo].[GameEmployeePoint](  
    Id [int] IDENTITY(1,1) NOT NULL,
    GameId [int]   NULL,
    EmployeeId [int]  NOT NULL,
    Points [int]  NOT NULL,   
    Description [nvarchar](255)  NULL, 
    TransactionDate [DateTime] Not NULL,
      CONSTRAINT [PK_GameEmployeePoint] PRIMARY KEY CLUSTERED 
    (
        [id] ASC
    )WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
    ) ON [PRIMARY]
    GO    
 
  
 CREATE TABLE [dbo].[GamePrize](  
    Id [int] IDENTITY(1,1) NOT NULL,
    Points [int]  NOT NULL,   
    ProgramID [int]    NULL,  
    SiteId [int]    NULL,  
    Active [bit] NOT NULL,     
    Description [nvarchar](255)  NULL, 

      CONSTRAINT [PK_GamePrize] PRIMARY KEY CLUSTERED 
    (
        [id] ASC
    )WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
    ) ON [PRIMARY]
    GO    
 
 
 

 ALTER TABLE Game drop COLUMN StoredProc ;                                                  
 go
 ALTER TABLE Game ADD StoredProcId  [int] NULL;


ALTER TABLE Survey ADD DenyAnonymous  [bit] NULL  ;
go 
ALTER TABLE Survey ADD Trainer  [bigint] NULL  ; 


CREATE TABLE [dbo].[SuperAdmin](
    [id] [int] IDENTITY(1,1) NOT NULL,
    [EmployeeId] [int] NOT NULL,
    [Name]  [nvarchar](255)  NULL, 
    [Active] [bit] NOT NULL,
 CONSTRAINT [PK_SuperAdmin] PRIMARY KEY CLUSTERED 
(
    [id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
GO 

 
GO
DROP TABLE [dbo].[SPMetrics]
GO
CREATE TABLE [dbo].[SPMetrics](
    [Id] [int] IDENTITY(1,1) NOT NULL,
    [Name]  [nvarchar](255) NOT NULL, 
    [Period]  [nvarchar](255) NOT NULL, 
    [Site]  [nvarchar](255)  NULL, 
    [Program]  [nvarchar](255)  NULL, 
    [Client]  [nvarchar](255)  NULL, 
    [Metric]  [nvarchar](255)  NULL, 
    [Description]  [nvarchar](1000)  NULL, 
    [Active] [bit] NOT NULL,
 CONSTRAINT [PK_SPMetrics] PRIMARY KEY CLUSTERED 
(
    [id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
GO 
 
 
 
 USE [EmployeePortal]
GO
 

EXECUTE @RC = [dbo].[usp_Agent_Gamification] 
   'DWY'
  ,'ATT BNC'
  ,'Monthly'
  ,'Commission'
  ,'2024-02-02'
GO
 
Msg 229, Level 14, State 5, Procedure usp_Agent_Gamification, Line 1 [Batch Start Line 0]
The EXECUTE permission was denied on the object 'usp_Agent_Gamification', database 'EmployeePortal', schema 'dbo'.

Completion time: 2024-02-14T17:10:59.9148904+02:00
 
 
 
select *,ROW_NUMBER() OVER(ORDER BY employeeid ASC) AS Row  from gameresult


select EmployeeId,rn  from (
select top 3 EmployeeId,ROW_NUMBER() OVER(ORDER BY Score DESC) AS rn  from gameresult  where  GameJobId=9  
) t 

 

Add-Migration IsMarriedToUserAdded
Update-Database
Script-Migration -From trtext


ALTER TABLE "Memorials" ADD "EventName" character varying(1000) ;
ALTER TABLE "Memorials" ADD "EventDate" date  NULL ;
 

DROP DATABASE test;

CREATE DATABASE test
 ;

USE test;
 
 



 




