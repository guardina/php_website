CREATE TABLE refdata_partner (
        gln                         BIGINT UNSIGNED
    ,   effective_dt                DATE
    ,   expiry_dt                   DATE DEFAULT '9999-12-31'
    ,   status_date                 DATE
    ,   ptype                       VARCHAR(3)
    ,   status                      VARCHAR(1)
    ,   lang                        VARCHAR(2)
    ,   descr1                      VARCHAR(255)
    ,   descr2                      VARCHAR(255)

    ,   PRIMARY KEY (gln, effective_dt, expiry_dt)
);



CREATE TABLE refdata_partner_role (
        gln                         BIGINT UNSIGNED
    ,   role_nr                     SMALLINT UNSIGNED
    ,   effective_dt                DATE
    ,   expiry_dt                   DATE DEFAULT '9999-12-31'
    ,   type                        VARCHAR(255)
    ,   street                      VARCHAR(255)
    ,   strno                       VARCHAR(255)
    ,   pobox                       VARCHAR(255)
    ,   zip                         VARCHAR(10)
    ,   city                        VARCHAR(255)
    ,   ctn                         VARCHAR(2)
    ,   cntry                       VARCHAR(2)

    ,   PRIMARY KEY (gln, role_nr, effective_dt, expiry_dt)
);