CREATE TABLE Doctors (  
    gln bigint(20) unsigned NOT NULL,
    lastName VARCHAR(70),
    firstName VARCHAR(70),
    isActive BOOLEAN,
    profession_textDe VARCHAR(70),
    profession_textFr VARCHAR(70),
    profession_textIt VARCHAR(70),
    profession_textEn VARCHAR(70),
    cetTitles_textDe VARCHAR(70),
    cetTitles_textFr VARCHAR(70),
    cetTitles_textIt VARCHAR(70),
    cetTitles_textEn VARCHAR(70),
    canton_textDe VARCHAR(70),
    canton_textFr VARCHAR(70),
    canton_textIt VARCHAR(70),
    canton_textEn VARCHAR(70),
    PRIMARY KEY (gln)
);