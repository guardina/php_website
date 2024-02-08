-- Create the following tables for stammdaten_gln database:
-- prefix + psy + (gln, languages, nationalities, diplomas, 
-- cetTitles, permissions, permissionAddresses).


USE stammdaten_gln;

CREATE TABLE psy_gln (
        gln                 BIGINT UNSIGNED
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


CREATE TABLE psy_nationalities (
        gln             	BIGINT UNSIGNED 
    ,   nationalityDe 		VARCHAR(40)
    ,   nationalityFr 		VARCHAR(40)
    ,   nationalityIt 		VARCHAR(40)
    ,   nationalityEn 		VARCHAR(40)
    ,   effective_dt        DATE DEFAULT current_date 
    ,   expiry_dt           DATE DEFAULT '9999-12-31'

    ,   PRIMARY KEY (gln, effective_dt, expiry_dt, nationalityEn)
    ,   FOREIGN KEY (gln, effective_dt, expiry_dt)
            REFERENCES psy_gln
            ON DELETE CASCADE
            ON UPDATE CASCADE
);


CREATE TABLE psy_languages (
        gln         		BIGINT UNSIGNED 
    ,   languageDe  		VARCHAR(40)
    ,   languageFr  		VARCHAR(40)
    ,   languageIt  		VARCHAR(40)
    ,   languageEn  		VARCHAR(40)
    ,   effective_dt        DATE DEFAULT current_date 
    ,   expiry_dt           DATE DEFAULT '9999-12-31'

    ,   PRIMARY KEY (gln, effective_dt, expiry_dt, languageEn)
    ,   FOREIGN KEY (gln, effective_dt, expiry_dt)
            REFERENCES psy_gln
            ON DELETE CASCADE
            ON UPDATE CASCADE
);


CREATE TABLE psy_diplomas (
        gln                 BIGINT UNSIGNED
    ,   professionDe	    VARCHAR(50)
    ,   professionFr	    VARCHAR(50)
    ,   professionIt	    VARCHAR(50)
    ,   professionEn	    VARCHAR(50)
    ,   diplomaTypeDe	    VARCHAR(100)
    ,   diplomaTypeFr	    VARCHAR(100)
    ,   diplomaTypeIt	    VARCHAR(100)
    ,   diplomaTypeEn	    VARCHAR(100)
    ,   issuanceDate        DATETIME
    ,   issuanceCountryDe   VARCHAR(40)
    ,   issuanceCountryFr   VARCHAR(40)
    ,   issuanceCountryIt   VARCHAR(40)
    ,   issuanceCountryEn   VARCHAR(40)
    ,   datePsyko           DATETIME
    ,   effective_dt        DATE DEFAULT current_date 
    ,   expiry_dt           DATE DEFAULT '9999-12-31'

    ,   PRIMARY KEY (gln, effective_dt, expiry_dt, professionEn)
    ,   FOREIGN KEY (gln, effective_dt, expiry_dt)
            REFERENCES psy_gln
            ON DELETE CASCADE
            ON UPDATE CASCADE
);


CREATE TABLE psy_cetTitles (
        gln                             BIGINT UNSIGNED
    ,   title_nr                        TINYINT UNSIGNED
    ,   professionDe	                VARCHAR(50)
    ,   professionFr	                VARCHAR(50)
    ,   professionIt	                VARCHAR(50)
    ,   professionEn	                VARCHAR(50)
    ,   titleTypeDe                     VARCHAR(100)
    ,   titleTypeFr                     VARCHAR(100)
    ,   titleTypeIt                     VARCHAR(100)
    ,   titleTypeEn                     VARCHAR(100)
    ,   titleKindDe                     VARCHAR(200)
    ,   titleKindFr                     VARCHAR(200)
    ,   titleKindIt                     VARCHAR(200)
    ,   titleKindEn                     VARCHAR(200)
    ,   issuanceCountryDe               VARCHAR(40)
    ,   issuanceCountryFr               VARCHAR(40)
    ,   issuanceCountryIt               VARCHAR(40)
    ,   issuanceCountryEn               VARCHAR(40)
    ,   issuanceDate                    DATETIME
    ,   datePsyko                       DATETIME
    ,   cetCourseDe                     VARCHAR(255)            
    ,   cetCourseFr                     VARCHAR(255)            
    ,   cetCourseIt                     VARCHAR(255)            
    ,   cetCourseEn                     VARCHAR(255)            
    ,   cetCourseName                   VARCHAR(255)
    ,   organisationName                VARCHAR(255)        
    ,   organisationZip                 VARCHAR(5)
    ,   organisationCity                VARCHAR(40) 
    ,   additionalIssuanceCountry       VARCHAR(40)                
    ,   additionalIssuanceDate          DATETIME
    ,   additionalCetCourse             VARCHAR(100)
    ,   additionalOrganisation          VARCHAR(200)            
    ,   providers90Days                 JSON
    ,   hasPermissionOtherThanNoLicence BOOL                      
    ,   effective_dt                    DATE DEFAULT current_date 
    ,   expiry_dt                       DATE DEFAULT '9999-12-31'

    ,   PRIMARY KEY (gln, effective_dt, expiry_dt, title_nr)
    ,   FOREIGN KEY (gln, effective_dt, expiry_dt)
            REFERENCES psy_gln (gln, effective_dt, expiry_dt)
            ON DELETE CASCADE
            ON UPDATE CASCADE
);


CREATE TABLE psy_permissions (
        gln                 BIGINT UNSIGNED
    ,   title_nr            TINYINT UNSIGNED
    ,   perm_nr             TINYINT UNSIGNED
    ,   legalBasisDe        VARCHAR(100)
    ,   legalBasisFr        VARCHAR(100)
    ,   legalBasisIt        VARCHAR(100)
    ,   legalBasisEn        VARCHAR(100)
    ,   permissionStateDe   VARCHAR(100)
    ,   permissionStateFr   VARCHAR(100)
    ,   permissionStateIt   VARCHAR(100)
    ,   permissionStateEn   VARCHAR(100)
    ,   cantonDe            VARCHAR(40)
    ,   cantonFr            VARCHAR(40)
    ,   cantonIt            VARCHAR(40)
    ,   cantonEn            VARCHAR(40)
    ,   timeLimitationDate  DATETIME
    ,   dateDecision        DATETIME
    ,   restrictions        JSON
    ,   effective_dt        DATE DEFAULT current_date 
    ,   expiry_dt           DATE DEFAULT '9999-12-31' 

    ,   PRIMARY KEY (gln, effective_dt, expiry_dt, title_nr, perm_nr)
    ,   FOREIGN KEY (gln, effective_dt, expiry_dt, title_nr) 
            REFERENCES psy_cetTitles (gln, effective_dt, expiry_dt, title_nr) 
            ON DELETE CASCADE
            ON UPDATE CASCADE
);


CREATE TABLE psy_permissionAddresses (
        gln                 BIGINT UNSIGNED
    ,   title_nr            TINYINT UNSIGNED
    ,   perm_nr             TINYINT UNSIGNED
    ,   addr_nr             TINYINT UNSIGNED
    ,   practiceCompanyName VARCHAR(200)
    ,   streetWithNumber    VARCHAR(100)
    ,   addition1           VARCHAR(100)
    ,   addition2           VARCHAR(100)
    ,   zipCity             VARCHAR(46)
    ,   zip                 VARCHAR(5)
    ,   city                VARCHAR(40)
    ,   phoneNumber         VARCHAR(30)
    ,   email               VARCHAR(100)
    ,   effective_dt        DATE DEFAULT current_date 
    ,   expiry_dt           DATE DEFAULT '9999-12-31' 

    ,   PRIMARY KEY (gln, effective_dt, expiry_dt, title_nr, perm_nr, addr_nr)
    ,   FOREIGN KEY (gln, effective_dt, expiry_dt, title_nr, perm_nr) 
            REFERENCES psy_permissions (gln, effective_dt, expiry_dt, title_nr, perm_nr) 
            ON DELETE CASCADE
            ON UPDATE CASCADE
);


-- Create merged stammdaten tables view without effective/expiry 
-- dates for update comparison;
create or replace view psy_effective_merged_nodate_v as (
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

        ,   n.nationalityDe
        ,   n.nationalityFr
        ,   n.nationalityIt
        ,   n.nationalityEn

        ,   l.languageDe 
        ,   l.languageFr
        ,   l.languageIt
        ,   l.languageEn

        ,   d.professionDe as d_professionDe
        ,   d.professionFr as d_professionFr
        ,   d.professionIt as d_professionIt
        ,   d.professionEn as d_professionEn
        ,   d.diplomaTypeDe as d_diplomaTypeDe
        ,   d.diplomaTypeFr as d_diplomaTypeFr
        ,   d.diplomaTypeIt as d_diplomaTypeIt
        ,   d.diplomaTypeEn as d_diplomaTypeEn
        ,   d.issuanceDate as d_issuanceDate
        ,   d.issuanceCountryDe as d_issuanceCountryDe
        ,   d.issuanceCountryFr as d_issuanceCountryFr
        ,   d.issuanceCountryIt as d_issuanceCountryIt
        ,   d.issuanceCountryEn as d_issuanceCountryEn
        ,   d.datePsyko as d_datePsyko

        ,   ct.title_nr
        ,   ct.professionDe as ct_professionDe
        ,   ct.professionFr as ct_professionFr
        ,   ct.professionIt as ct_professionIt
        ,   ct.professionEn as ct_professionEn
        ,   ct.titleTypeDe as ct_titleTypeDe
        ,   ct.titleTypeFr as ct_titleTypeFr
        ,   ct.titleTypeIt as ct_titleTypeIt
        ,   ct.titleTypeEn as ct_titleTypeEn
        ,   ct.titleKindDe as ct_titleKindDe
        ,   ct.titleKindFr as ct_titleKindFr
        ,   ct.titleKindIt as ct_titleKindIt
        ,   ct.titleKindEn as ct_titleKindEn
        ,   ct.issuanceCountryDe as ct_issuanceCountryDe
        ,   ct.issuanceCountryFr as ct_issuanceCountryFr
        ,   ct.issuanceCountryIt as ct_issuanceCountryIt
        ,   ct.issuanceCountryEn as ct_issuanceCountryEn
        ,   ct.issuanceDate as ct_issuanceDate
        ,   ct.datePsyko as ct_datePsyko
        ,   ct.cetCourseDe as ct_cetCourseDe
        ,   ct.cetCourseFr as ct_cetCourseFr
        ,   ct.cetCourseIt as ct_cetCourseIt
        ,   ct.cetCourseEn as ct_cetCourseEn
        ,   ct.cetCourseName as ct_cetCourseName
        ,   ct.organisationName as ct_organisationName
        ,   ct.organisationZip as ct_organisationZip
        ,   ct.organisationCity as ct_organisationCity
        ,   ct.additionalIssuanceCountry as ct_additionalIssuanceCountry
        ,   ct.additionalIssuanceDate as ct_additionalIssuanceDate
        ,   ct.additionalCetCourse as ct_additionalCetCourse
        ,   ct.additionalOrganisation as ct_additionalOrganisation
        ,   ct.providers90Days as ct_providers90Days
        ,   ct.hasPermissionOtherThanNoLicence as ct_hasPermissionOtherThanNoLicence

        ,   pe.perm_nr
        ,   pe.legalBasisDe as pe_legalBasisDe
        ,   pe.legalBasisFr as pe_legalBasisFr
        ,   pe.legalBasisIt as pe_legalBasisIt
        ,   pe.legalBasisEn as pe_legalBasisEn
        ,   pe.permissionStateDe as pe_permissionStateDe
        ,   pe.permissionStateFr as pe_permissionStateFr
        ,   pe.permissionStateIt as pe_permissionStateIt
        ,   pe.permissionStateEn as pe_permissionStateEn
        ,   pe.cantonDe as pe_cantonDe
        ,   pe.cantonFr as pe_cantonFr
        ,   pe.cantonIt as pe_cantonIt
        ,   pe.cantonEn as pe_cantonEn
        ,   pe.timeLimitationDate as pe_timeLimitationDate
        ,   pe.dateDecision as pe_dateDecision
        ,   pe.restrictions as pe_restrictions

        ,   pa.addr_nr as pa_addr_nr
        ,   pa.practiceCompanyName as pa_practiceCompanyName
        ,   pa.streetWithNumber as pa_streetWithNumber
        ,   pa.addition1 as pa_addition1
        ,   pa.addition2 as pa_addition2
        ,   pa.zipCity as pa_zipCity
        ,   pa.zip as pa_zip
        ,   pa.city as pa_city
        ,   pa.phoneNumber as pa_phoneNumber
        ,   pa.email as pa_email

    from psy_gln g
        left join psy_nationalities n           using (gln, effective_dt, expiry_dt)
        left join psy_languages l               using (gln, effective_dt, expiry_dt)
        left join psy_diplomas d                using (gln, effective_dt, expiry_dt)
        left join psy_cetTitles ct              using (gln, effective_dt, expiry_dt)
        left join psy_permissions pe            using (gln, effective_dt, expiry_dt, title_nr)
        left join psy_permissionAddresses pa    using (gln, effective_dt, expiry_dt, title_nr, perm_nr)
    where g.expiry_dt = '9999-12-31'
);