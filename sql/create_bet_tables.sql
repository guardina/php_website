-- Create the following tables for stammdaten_gln database:
-- prefix + bet + (companyGln, responsiblePersons).

USE stammdaten_gln;

CREATE TABLE bet_companyGln (
        bag_id              INT UNSIGNED
    ,   glnCompany          BIGINT UNSIGNED
    ,   companyName         VARCHAR(255)
    ,   additionalName      VARCHAR(255)
    ,   streetWithNumber    VARCHAR(255)
    ,   poBox               VARCHAR(255)
    ,   zip                 VARCHAR(255)
    ,   zipCity             VARCHAR(255)
    ,   city                VARCHAR(255)
    ,   cantonDe            VARCHAR(255)
    ,   cantonFr            VARCHAR(255)    
    ,   cantonIt            VARCHAR(255)    
    ,   cantonEn            VARCHAR(255)    
    ,   companyTypeDe       VARCHAR(255)
    ,   companyTypeFr       VARCHAR(255)
    ,   companyTypeIt       VARCHAR(255)
    ,   companyTypeEn       VARCHAR(255)
    ,   permissionBtmDe     VARCHAR(255)
    ,   permissionBtmFr     VARCHAR(255)
    ,   permissionBtmIt     VARCHAR(255)
    ,   permissionBtmEn     VARCHAR(255)
    ,   effective_dt        DATE DEFAULT current_date 
    ,   expiry_dt           DATE DEFAULT '9999-12-31'

    ,   PRIMARY KEY (bag_id, effective_dt, expiry_dt)
);


CREATE TABLE bet_responsiblePersons (
        bag_id              INT UNSIGNED
    ,   glnPerson           BIGINT UNSIGNED
    ,   familyName          VARCHAR(255)
    ,   firstName           VARCHAR(255)
    ,   effective_dt        DATE DEFAULT current_date 
    ,   expiry_dt           DATE DEFAULT '9999-12-31'

    ,   PRIMARY KEY (bag_id, familyName, firstName, effective_dt, expiry_dt)
    ,   FOREIGN KEY (bag_id, effective_dt, expiry_dt)
            REFERENCES bet_companyGln (bag_id, effective_dt, expiry_dt)
            ON UPDATE CASCADE
            ON DELETE CASCADE
);

-- Create merged stammdaten tables view without effective/expiry 
-- dates for update comparison;
create or replace view bet_effective_merged_nodate_v as (
    select      
        cg.bag_id
        ,   cg.glnCompany
        ,   cg.companyName
        ,   cg.additionalName
        ,   cg.streetWithNumber
        ,   cg.poBox
        ,   cg.zip
        ,   cg.zipCity
        ,   cg.city
        ,   cg.cantonDe
        ,   cg.cantonFr
        ,   cg.cantonIt
        ,   cg.cantonEn
        ,   cg.companyTypeDe
        ,   cg.companyTypeFr
        ,   cg.companyTypeIt
        ,   cg.companyTypeEn
        ,   cg.permissionBtmDe
        ,   cg.permissionBtmFr
        ,   cg.permissionBtmIt
        ,   cg.permissionBtmEn

        ,   rp.glnPerson as rp_glnPerson
        ,   rp.familyName as rp_familyName
        ,   rp.firstName as rp_firstName

    from bet_companyGln cg
        left join bet_responsiblePersons rp using (bag_id, effective_dt, expiry_dt)
    where cg.expiry_dt = '9999-12-31'
);