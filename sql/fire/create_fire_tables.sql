
use stammdaten_gln;

CREATE TABLE Doctor (
        gln                             BIGINT UNSIGNED
    ,   firstName                       VARCHAR(50)
    ,   lastName                        VARCHAR(50)
    ,   birthday                        DATE
    ,   sex                             VARCHAR(20)
    ,   ahv                             VARCHAR(50)
    ,   status                          VARCHAR(20)
    ,   status_reason                   VARCHAR(50)
    ,   title                           VARCHAR(20)
    ,   lp                              BOOL
    ,   la                              BOOL
    ,   students_nr                     INTEGER
    
    ,   PRIMARY KEY (gln)
);



CREATE TABLE Address(
        address_id                      INTEGER
    ,   address_type                    VARCHAR(50)
    ,   address                         VARCHAR(200)
    ,   plz                             INTEGER
    ,   city                            VARCHAR(50)
    ,   telephone_praxis                VARCHAR(20)
    ,   email_praxis                    VARCHAR(50)
    ,   fax_praxis                      VARCHAR(20)
    ,   website                         VARCHAR(100)

    ,   PRIMARY KEY (address_id)
);



CREATE TABLE Practice (
        practice_id                     INTEGER
    ,   practice_form                   VARCHAR(50)
    ,   practice_opening_dt             DATE
    ,   practice_certificate            VARCHAR(200)
    ,   doctor_nr                       INTEGER

    ,   PRIMARY KEY (practice_id)
);


CREATE TABLE Apprenticeship (
        apprenticeship_id               INTEGER
    ,   apprenticeship_name_short       VARCHAR(10)
    ,   apprenticeship_name_long        VARCHAR(50)
    ,   year                            INTEGER
    ,   semester                        VARCHAR(50)
    ,   semester_short                  VARCHAR(10)
    ,   credits                         INTEGER

    ,   PRIMARY KEY (apprenticeship_id)
);



CREATE TABLE Project (
        project_id                      INTEGER
    ,   project_name_short              VARCHAR(50)
    ,   project_name_long               VARCHAR(150)
    ,   start_dt                        DATE
    ,   end_dt                          DATE

    ,   PRIMARY KEY (project_id)
);






CREATE TABLE t_practice_assistance (
        gln_lp                          BIGINT UNSIGNED
    ,   gln_pa                          BIGINT UNSIGNED
    ,   start_dt                        DATE
    ,   end_dt                          DATE
    
    ,   FOREIGN KEY (gln_lp) REFERENCES Doctor(gln)
        ON DELETE CASCADE
        ON UPDATE CASCADE
    ,   FOREIGN KEY (gln_pa) REFERENCES Doctor(gln)
        ON DELETE CASCADE
        ON UPDATE CASCADE
    ,   PRIMARY KEY (gln_lp, gln_pa)
);