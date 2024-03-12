CREATE TABLE IF NOT EXISTS med_ids (
            id          INT NOT NULL
    ,       bucket      INT NOT NULL
    ,       round_1     BOOL
    ,       round_2     BOOL
    ,        PRIMARY KEY (id)
);


CREATE TABLE IF NOT EXISTS psy_ids (
            id          INT NOT NULL
    ,       bucket      INT NOT NULL
    ,       round_1     BOOL
    ,       round_2     BOOL
    ,        PRIMARY KEY (id)
);


CREATE TABLE IF NOT EXISTS bet_ids (
            id          INT NOT NULL
    ,       bucket      INT NOT NULL
    ,       round_1     BOOL
    ,       round_2     BOOL
    ,        PRIMARY KEY (id)
);
