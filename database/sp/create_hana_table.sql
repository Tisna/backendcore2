CREATE COLUMN TABLE IMIP_ERESV.RESV_H (
    "DocNum" INTEGER null,
    "DocDate" DATE null,
    "RequiredDate" DATE null,
    "Requester" INTEGER null,
    "Division" VARCHAR (200) null,
    "Department" VARCHAR (200) null,
    "Company" VARCHAR (200) null,
    "Memo" NVARCHAR (5000) null,
    "Canceled" VARCHAR (20) null,
    "DocStatus" VARCHAR (20) null,
    "ApprovalStatus" VARCHAR (20) null,
    "ApprovalKey" INTEGER null,
    "isConfirmed" VARCHAR (20) null,
    "ConfirmDate" DATE null,
    "ConfirmBy" INTEGER null,
    "SAP_GIRNo" INTEGER null,
    "SAP_TrfNo" INTEGER null,
    "SAP_PRNo" INTEGER null,
    "CreateDate" DATE null,
    "CreateTime" TIME null,
    "CreatedBy" INTEGER null,
    "UpdateDate" DATE null,
    "UpdateTime" TIME null,
    "UpdatedBy" INTEGER null,
    "U_DocEntry" BIGINT not null,
    "RequestType" VARCHAR (50) null,
    U_NIK VARCHAR (30) null,
    "WhsCode" VARCHAR (20) null,
    "WhTo" VARCHAR (20) null,
    "Token" VARCHAR (200) null,
    "CreatedName" VARCHAR (200) null,
    "RequesterName" VARCHAR (200) null,
    "UrgentReason" VARCHAR (200) null,
    "ItemType" VARCHAR (20) null,
    PRIMARY KEY ("U_DocEntry")
    );



CREATE COLUMN TABLE IMIP_ERESV.RESV_D (
    "U_DocEntry" BIGINT null,
    "LineNum" INTEGER null,
    "ItemCode" VARCHAR (20) null,
    "ItemName" NVARCHAR (5000) null,
    "WhsCode" VARCHAR (20) null,
    "UoMCode" VARCHAR (20) null,
    "UoMName" VARCHAR (20) null,
    "ReqQty" DOUBLE null,
    "ReqDate" DATE null,
    "ReqNotes" NVARCHAR (5000) null,
    "OtherResvNo" VARCHAR (100) null,
    "RequestType" VARCHAR (20) null,
    "QtyReadyIssue" DOUBLE null,
    "LineStatus" VARCHAR (20) null,
    "SAP_GIRNo" INTEGER null,
    "SAP_TrfNo" INTEGER null,
    "SAP_PRNo" INTEGER null,
    "LineEntry" BIGINT not null,
    "ItemCategory" VARCHAR (30) null,
    "OIGRDocNum" BIGINT null,
    "InvntItem" VARCHAR (20) default 'Y' null,
    PRIMARY KEY ("LineEntry")
    );

CREATE COLUMN TABLE IMIP_ERESV.U_OITM (
    "U_Description" NVARCHAR (254) null,
    "U_UoM" NVARCHAR (20) null,
    "U_Status" NVARCHAR (20) null,
    "U_Remarks" NVARCHAR (254) null,
    "U_Supporting" NVARCHAR (254) null,
    "U_CreatedBy" INTEGER null,
    "U_DocEntry" BIGINT not null,
    "U_Comments" VARCHAR (200) null,
    "U_CreatedAt" TIMESTAMP default CURRENT_TIMESTAMP null,
    PRIMARY KEY ("U_DocEntry")
    );


