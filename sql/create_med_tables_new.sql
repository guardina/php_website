-- Create the following tables for stammdaten_gln database:
-- prefix + med + (gln, languages, nationalities, professions, cetTitles, 
-- privateLawCetTitles, permissions, permissionAddress)

USE stammdaten_gln;

CREATE TABLE med_gln (
        gln                 BIGINT UNSIGNED
    ,   id                  INT
    ,   lastName            VARCHAR(70)
    ,   firstName           VARCHAR(70)
    ,   genderDe            VARCHAR(20)
    ,   genderFr            VARCHAR(20)
    ,   genderIt            VARCHAR(20)
    ,   genderEn            VARCHAR(20)
    ,   yearOfBirth         YEAR
    ,   uid                 VARCHAR(30)
    ,   hasPermission       BOOL
    ,   hasProvider90Days   BOOL
    ,   effective_dt        DATE DEFAULT current_date
    ,   expiry_dt           DATE DEFAULT '9999-12-31'

    ,   PRIMARY KEY (gln, effective_dt, expiry_dt)
);

CREATE TABLE med_nationalities (
        gln             	BIGINT UNSIGNED 
    ,   nationalityDe       VARCHAR(40)
    ,   nationalityFr       VARCHAR(40)
    ,   nationalityIt       VARCHAR(40)
    ,   nationalityEn       VARCHAR(40)
    ,   effective_dt        DATE DEFAULT current_date
    ,   expiry_dt           DATE DEFAULT '9999-12-31'

    ,   PRIMARY KEY (gln, effective_dt, expiry_dt)
    ,   FOREIGN KEY (gln, effective_dt, expiry_dt)
            REFERENCES med_gln (gln, effective_dt, expiry_dt)
            ON DELETE CASCADE
            ON UPDATE CASCADE);

CREATE TABLE med_languages (
        gln         		BIGINT UNSIGNED 
    ,   languageDe  		VARCHAR(40)
    ,   languageFr  		VARCHAR(40)
    ,   languageIt  		VARCHAR(40)
    ,   languageEn  		VARCHAR(40)
    ,   effective_dt        DATE DEFAULT current_date 
    ,   expiry_dt           DATE DEFAULT '9999-12-31'

    ,   PRIMARY KEY (gln, effective_dt, expiry_dt)
    ,   FOREIGN KEY (gln, effective_dt, expiry_dt)
            REFERENCES med_gln (gln, effective_dt, expiry_dt)
            ON DELETE CASCADE
            ON UPDATE CASCADE);

CREATE TABLE med_professions (
        gln                             BIGINT UNSIGNED 
    ,   professionDe		            VARCHAR(50)
    ,   professionFr		            VARCHAR(50)
    ,   professionIt		            VARCHAR(50)
    ,   professionEn		            VARCHAR(50)
    ,   diplomaTypeDe		            VARCHAR(100)
    ,   diplomaTypeFr		            VARCHAR(100)
    ,   diplomaTypeIt		            VARCHAR(100)
    ,   diplomaTypeEn		            VARCHAR(100)
    ,   issuanceDate                    DATETIME
    ,   issuanceCountryDe	            VARCHAR(40)
    ,   issuanceCountryFr	            VARCHAR(40)
    ,   issuanceCountryIt	            VARCHAR(40)
    ,   issuanceCountryEn	            VARCHAR(40)
    ,   dateMebeko                      DATETIME
    ,   providers90Days     			JSON
    ,   hasPermissionOtherThanNoLicence BOOL
    ,   effective_dt                    DATE DEFAULT current_date 
    ,   expiry_dt                       DATE DEFAULT '9999-12-31'

    ,   PRIMARY KEY (gln, effective_dt, expiry_dt, professionEn)
    ,   FOREIGN KEY (gln, effective_dt, expiry_dt)
            REFERENCES med_gln (gln, effective_dt, expiry_dt)
            ON DELETE CASCADE
            ON UPDATE CASCADE
);

CREATE TABLE med_cetTitles (
        gln                 BIGINT UNSIGNED 
    ,   professionEn        VARCHAR(50) 
    ,   titleTypeDe			VARCHAR(100)
    ,   titleTypeFr			VARCHAR(100)
    ,   titleTypeIt			VARCHAR(100)
    ,   titleTypeEn			VARCHAR(100)
    ,   titleKindDe			VARCHAR(200)
    ,   titleKindFr			VARCHAR(200)
    ,   titleKindIt			VARCHAR(200)
    ,   titleKindEn			VARCHAR(200)
    ,   issuanceCountryDe   VARCHAR(40)
    ,   issuanceCountryFr   VARCHAR(40)
    ,   issuanceCountryIt   VARCHAR(40)
    ,   issuanceCountryEn   VARCHAR(40)
    ,   issuanceDate        DATETIME
    ,   dateMebeko          DATETIME
    ,   effective_dt        DATE DEFAULT current_date 
    ,   expiry_dt           DATE DEFAULT '9999-12-31'

    ,   PRIMARY KEY (gln, effective_dt, expiry_dt, professionEn)
    ,   FOREIGN KEY (gln, effective_dt, expiry_dt, professionEn)
            REFERENCES med_professions (gln, effective_dt, expiry_dt, professionEn)
            ON DELETE CASCADE
            ON UPDATE CASCADE
);

CREATE TABLE med_privateLawCetTitles (
        gln                 BIGINT UNSIGNED 
    ,   professionEn        VARCHAR(50) 
    ,   titleTypeDe         VARCHAR(100)
    ,   titleTypeFr         VARCHAR(100)
    ,   titleTypeIt         VARCHAR(100)
    ,   titleTypeEn         VARCHAR(100)
    ,   titleKindDe         VARCHAR(200)
    ,   titleKindFr         VARCHAR(200)
    ,   titleKindIt         VARCHAR(200)
    ,   titleKindEn         VARCHAR(200)
    ,   issuanceDate        DATETIME
    ,   effective_dt        DATE DEFAULT current_date 
    ,   expiry_dt           DATE DEFAULT '9999-12-31'

    ,   PRIMARY KEY (gln, effective_dt, expiry_dt, professionEn)
    ,   FOREIGN KEY (gln, effective_dt, expiry_dt, professionEn)
            REFERENCES med_professions (gln, effective_dt, expiry_dt, professionEn)
            ON DELETE CASCADE
            ON UPDATE CASCADE
);

CREATE TABLE med_permissions (
        gln                         BIGINT UNSIGNED
    ,   professionEn                VARCHAR(50) 
    ,   permissionTypeDe            VARCHAR(100)
    ,   permissionTypeFr            VARCHAR(100)
    ,   permissionTypeIt            VARCHAR(100)
    ,   permissionTypeEn            VARCHAR(100)
    ,   permissionStateDe           VARCHAR(100)    
    ,   permissionStateFr           VARCHAR(100)
    ,   permissionStateIt           VARCHAR(100)
    ,   permissionStateEn           VARCHAR(100)
    ,   permissionActivityStateDe   VARCHAR(50)
    ,   permissionActivityStateFr   VARCHAR(50)
    ,   permissionActivityStateIt   VARCHAR(50)
    ,   permissionActivityStateEn   VARCHAR(50)
    ,   cantonDe                    VARCHAR(40)
    ,   cantonFr                    VARCHAR(40)
    ,   cantonIt                    VARCHAR(40)
    ,   cantonEn                    VARCHAR(40)
    ,   dateDecision                DATETIME
    ,   dateActivity                DATETIME
    ,   restrictions                JSON
    ,   effective_dt                DATE DEFAULT current_date 
    ,   expiry_dt                   DATE DEFAULT '9999-12-31'

    ,   PRIMARY KEY (gln, effective_dt, expiry_dt, professionEn)
    ,   FOREIGN KEY (gln, effective_dt, expiry_dt, professionEn)
            REFERENCES med_professions (gln, effective_dt, expiry_dt, professionEn)
            ON DELETE CASCADE
            ON UPDATE CASCADE
);


CREATE TABLE med_permissionAddress (
        gln                 BIGINT UNSIGNED
    ,   professionEn        VARCHAR(50)
    ,   dateDecision        DATETIME
    ,   practiceCompanyName VARCHAR(200)
    ,   streetWithNumber    VARCHAR(100)
    ,   zipCity             VARCHAR(46)
    ,   zip                 VARCHAR(5)
    ,   city                VARCHAR(40)
    ,   phoneNumber1        VARCHAR(30)
    ,   phoneNumber2        VARCHAR(30)
    ,   phoneNumber3        VARCHAR(30)
    ,   faxNumber           VARCHAR(20)
    ,   uid                 VARCHAR(30)
    ,   selfDispensation    BOOL
    ,   permissionBtm       BOOL
    ,   effective_dt        DATE DEFAULT current_date 
    ,   expiry_dt           DATE DEFAULT '9999-12-31'

    ,   PRIMARY KEY (gln, effective_dt, expiry_dt, professionEn)
    ,   FOREIGN KEY (gln, effective_dt, expiry_dt, professionEn) 
            REFERENCES med_permissions (gln, effective_dt, expiry_dt, professionEn) 
            ON DELETE CASCADE
            ON UPDATE CASCADE
);


-- Create merged stammdaten tables view without effective/expiry 
-- dates for update comparison;
create or replace view med_effective_merged_nodate_v as (
    select 
            g.gln
        ,   g.lastName        
        ,   g.firstName                    
        ,   g.genderDe
        ,   g.genderFr
        ,   g.genderIt 
        ,   g.genderEn
        ,   g.yearOfBirth
        ,   g.uid         
        ,   g.hasPermission
        ,   g.hasProvider90Days

        ,   l.languageDe 
        ,   l.languageFr
        ,   l.languageIt
        ,   l.languageEn

        ,   n.nationalityDe
        ,   n.nationalityFr
        ,   n.nationalityIt
        ,   n.nationalityEn

        ,   p.professionDe
        ,   p.professionFr
        ,   p.professionIt
        ,   p.professionEn
        ,   p.diplomaTypeDe as prof_diplomaTypeDe
        ,   p.diplomaTypeFr as prof_diplomaTypeFr
        ,   p.diplomaTypeIt as prof_diplomaTypeIt
        ,   p.diplomaTypeEn as prof_diplomaTypeEn
        ,   p.issuanceDate as prof_issuanceDate
        ,   p.issuanceCountryDe as prof_issuanceCountryDe
        ,   p.issuanceCountryFr as prof_issuanceCountryFr
        ,   p.issuanceCountryIt as prof_issuanceCountryIt
        ,   p.issuanceCountryEn as prof_issuanceCountryEn
        ,   p.dateMebeko as prof_dateMebeko
        ,   p.providers90Days as prof_providers90Days
        ,   p.hasPermissionOtherThanNoLicence as prof_hasPermissionOtherThanNoLicence

        ,   pe.permissionTypeDe as perm_TypeDe                                         
        ,   pe.permissionTypeFr as perm_TypeFr                                          
        ,   pe.permissionTypeIt as perm_TypeIt                                             
        ,   pe.permissionTypeEn as perm_TypeEn                                                               
        ,   pe.permissionStateDe as perm_StateDe
        ,   pe.permissionStateFr as perm_StateFr    
        ,   pe.permissionStateIt as perm_StateIt      
        ,   pe.permissionStateEn as perm_StateEn
        ,   pe.permissionActivityStateDe as perm_ActivityStateDe
        ,   pe.permissionActivityStateFr as perm_ActivityStateFr
        ,   pe.permissionActivityStateIt as perm_ActivityStateIt
        ,   pe.permissionActivityStateEn as perm_ActivityStateEn
        ,   pe.cantonDe as perm_cantonDe 
        ,   pe.cantonFr as perm_cantonFr 
        ,   pe.cantonIt as perm_cantonIt 
        ,   pe.cantonEn as perm_cantonEn 
        ,   pe.dateDecision as perm_dateDecision           
        ,   pe.dateActivity as perm_dateActivity           
        ,   pe.restrictions as perm_restrictions

        ,   ct.titleTypeDe as cet_titleTypeDe                        
        ,   ct.titleTypeFr as cet_titleTypeFr            
        ,   ct.titleTypeIt as cet_titleTypeIt                       
        ,   ct.titleTypeEn as cet_titleTypeEn               
        ,   ct.titleKindDe as cet_titleKindDe                                                      
        ,   ct.titleKindFr as cet_titleKindFr                                                     
        ,   ct.titleKindIt as cet_titleKindIt                                                  
        ,   ct.titleKindEn as cet_titleKindEn                                                         
        ,   ct.issuanceCountryDe as cet_issuanceCountryDe
        ,   ct.issuanceCountryFr as cet_issuanceCountryFr
        ,   ct.issuanceCountryIt as cet_issuanceCountryIt
        ,   ct.issuanceCountryEn as cet_issuanceCountryEn
        ,   ct.issuanceDate as cet_issuanceDate           
        ,   ct.dateMebeko as cet_dateMebeko

        ,   pl.titleTypeDe as plCet_titleTypeDe
        ,   pl.titleTypeFr as plCet_titleTypeFr
        ,   pl.titleTypeIt as plCet_titleTypeIt
        ,   pl.titleTypeEn as plCet_titleTypeEn
        ,   pl.titleKindDe as plCet_titleKindDe
        ,   pl.titleKindFr as plCet_titleKindFr
        ,   pl.titleKindIt as plCet_titleKindIt
        ,   pl.titleKindEn as plCet_titleKindEn
        ,   pl.issuanceDate as plCet_issuanceDate

        ,   pa.dateDecision as perm_addr_dateDecision
        ,   pa.practiceCompanyName as perm_addr_practiceCompanyName                                        
        ,   pa.streetWithNumber as perm_addr_streetWithNumber               
        ,   pa.zipCity as perm_addr_zipCity                              
        ,   pa.zip as perm_addr_zip  
        ,   pa.city as perm_addr_city                               
        ,   pa.phoneNumber1 as perm_addr_phoneNumber1  
        ,   pa.phoneNumber2 as perm_addr_phoneNumber2
        ,   pa.phoneNumber3 as perm_addr_phoneNumber3
        ,   pa.faxNumber as perm_addr_faxNumber        
        ,   pa.uid as perm_addr_uid
        ,   pa.selfDispensation as perm_addr_selfDispensation
        ,   pa.permissionBtm as perm_addr_permissionBtm

    from med_gln g
        left join med_languages l               using (gln, effective_dt, expiry_dt)
        left join med_nationalities n           using (gln, effective_dt, expiry_dt)
        left join med_professions p             using (gln, effective_dt, expiry_dt)
        left join med_permissions pe            using (gln, effective_dt, expiry_dt, professionEn)
        left join med_cetTitles ct              using (gln, effective_dt, expiry_dt, professionEn)
        left join med_privateLawCetTitles pl    using (gln, effective_dt, expiry_dt, professionEn)
        left join med_permissionAddress pa      using (gln, effective_dt, expiry_dt, professionEn)
    where g.expiry_dt = '9999-12-31'
);