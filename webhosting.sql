CREATE TABLE Felhasznalo (
                             felhasznalo_id NUMBER PRIMARY KEY,
                             felhasznalonev VARCHAR2(100) NOT NULL,
                             email VARCHAR2(100) UNIQUE NOT NULL,
                             jelszo VARCHAR2(100) NOT NULL,
                             szerepkor VARCHAR2(50) NOT NULL
);

CREATE TABLE Dijcsomag (
                           dijcsomag_id NUMBER PRIMARY KEY,
                           dijcsomag_nev VARCHAR2(100) NOT NULL,
                           ar NUMBER(10) NOT NULL
);

CREATE TABLE Rendelkezik (
                             rendelkezes_id NUMBER PRIMARY KEY,
                             felhasznalo_id NUMBER NOT NULL,
                             dijcsomag_id NUMBER NOT NULL,
                             CONSTRAINT fk_rendelkezik_felhasznalo FOREIGN KEY (felhasznalo_id) REFERENCES Felhasznalo(felhasznalo_id) ON DELETE CASCADE,
                             CONSTRAINT fk_rendelkezik_dijcsomag FOREIGN KEY (dijcsomag_id) REFERENCES Dijcsomag(dijcsomag_id) ON DELETE CASCADE
);

CREATE TABLE Szamla (
                        szamla_id NUMBER PRIMARY KEY,
                        osszeg NUMBER(10) NOT NULL,
                        datum DATE NOT NULL,
                        allapot VARCHAR2(50) NOT NULL,
                        rendelkezes_id NUMBER NOT NULL,
                        CONSTRAINT fk_szamla_rendelkezes FOREIGN KEY (rendelkezes_id) REFERENCES Rendelkezik(rendelkezes_id) ON DELETE CASCADE
);

CREATE TABLE Webtarhely (
                            webtarhely_id NUMBER PRIMARY KEY,
                            meret NUMBER(10) NOT NULL,
                            statusz VARCHAR2(50),
                            letrehozas DATE NOT NULL,
                            felhasznalo_id NUMBER NOT NULL,
                            CONSTRAINT fk_webtarhely_felhasznalo FOREIGN KEY (felhasznalo_id) REFERENCES Felhasznalo(felhasznalo_id) ON DELETE CASCADE
);

CREATE TABLE Domain (
                        domain_nev VARCHAR2(100) PRIMARY KEY,
                        domain_tipus VARCHAR2(50) NOT NULL,
                        lejarati_datum DATE NOT NULL,
                        webtarhely_id NUMBER NOT NULL,
                        CONSTRAINT fk_domain_webtarhely FOREIGN KEY (webtarhely_id) REFERENCES Webtarhely(webtarhely_id) ON DELETE CASCADE
);

CREATE TABLE Adatbazis (
                           adatbazis_id NUMBER PRIMARY KEY,
                           adatbazis_tipus VARCHAR2(50) NOT NULL,
                           adatbazis_meret NUMBER(10) NOT NULL,
                           webtarhely_id NUMBER NOT NULL,
                           CONSTRAINT fk_adatbazis_webtarhely FOREIGN KEY (webtarhely_id) REFERENCES Webtarhely(webtarhely_id) ON DELETE CASCADE
);

CREATE TABLE Reklam (
                        reklam_id NUMBER PRIMARY KEY,
                        szoveg VARCHAR2(255),
                        hivatkozas VARCHAR2(255) NOT NULL,
                        felhasznalo_id NUMBER NOT NULL,
                        CONSTRAINT fk_reklam_felhasznalo FOREIGN KEY (felhasznalo_id) REFERENCES Felhasznalo(felhasznalo_id) ON DELETE CASCADE
);

CREATE TABLE Megjelenit (
                            webtarhely_id NUMBER,
                            reklam_id NUMBER,
                            PRIMARY KEY (webtarhely_id, reklam_id),
                            CONSTRAINT fk_megjelenit_webtarhely FOREIGN KEY (webtarhely_id) REFERENCES Webtarhely(webtarhely_id) ON DELETE CASCADE,
                            CONSTRAINT fk_megjelenit_reklam FOREIGN KEY (reklam_id) REFERENCES Reklam(reklam_id) ON DELETE CASCADE
);

CREATE SEQUENCE felhasznalo_id_seq START WITH 1 INCREMENT BY 1 NOCACHE NOCYCLE;
CREATE SEQUENCE dijcsomag_id_seq START WITH 1 INCREMENT BY 1 NOCACHE NOCYCLE;
CREATE SEQUENCE rendelkezes_id_seq START WITH 1 INCREMENT BY 1 NOCACHE NOCYCLE;
CREATE SEQUENCE szamla_id_seq START WITH 1 INCREMENT BY 1 NOCACHE NOCYCLE;
CREATE SEQUENCE webtarhely_id_seq START WITH 1 INCREMENT BY 1 NOCACHE NOCYCLE;
CREATE SEQUENCE reklam_id_seq START WITH 1 INCREMENT BY 1 NOCACHE NOCYCLE;
CREATE SEQUENCE adatbazis_id_seq START WITH 1 INCREMENT BY 1 NOCACHE NOCYCLE;

CREATE OR REPLACE TRIGGER trg_felhasznalo_id
BEFORE INSERT ON Felhasznalo
FOR EACH ROW
BEGIN
    :NEW.felhasznalo_id := felhasznalo_id_seq.NEXTVAL;
END;
/

CREATE OR REPLACE TRIGGER trg_dijcsomag_id
BEFORE INSERT ON Dijcsomag
FOR EACH ROW
BEGIN
    :NEW.dijcsomag_id := dijcsomag_id_seq.NEXTVAL;
END;
/

CREATE OR REPLACE TRIGGER trg_rendelkezes_id
BEFORE INSERT ON Rendelkezik
FOR EACH ROW
BEGIN
    :NEW.rendelkezes_id := rendelkezes_id_seq.NEXTVAL;
END;
/

CREATE OR REPLACE TRIGGER trg_szamla_id
BEFORE INSERT ON Szamla
FOR EACH ROW
BEGIN
    :NEW.szamla_id := szamla_id_seq.NEXTVAL;
END;
/

CREATE OR REPLACE TRIGGER trg_webtarhely_id
BEFORE INSERT ON Webtarhely
FOR EACH ROW
BEGIN
    :NEW.webtarhely_id := webtarhely_id_seq.NEXTVAL;
END;
/

CREATE OR REPLACE TRIGGER trg_reklam_id
BEFORE INSERT ON Reklam
FOR EACH ROW
BEGIN
    :NEW.reklam_id := reklam_id_seq.NEXTVAL;
END;
/

CREATE OR REPLACE TRIGGER trg_adatbazis_id
BEFORE INSERT ON Adatbazis
FOR EACH ROW
BEGIN
    :NEW.adatbazis_id := adatbazis_id_seq.NEXTVAL;
END;
/

INSERT INTO Dijcsomag (dijcsomag_nev, ar) VALUES ('Alap', 5000);
INSERT INTO Dijcsomag (dijcsomag_nev, ar) VALUES ('Standard', 10000);
INSERT INTO Dijcsomag (dijcsomag_nev, ar) VALUES ('Prémium', 15000);
INSERT INTO Dijcsomag (dijcsomag_nev, ar) VALUES ('Business', 20000);
INSERT INTO Dijcsomag (dijcsomag_nev, ar) VALUES ('Enterprise', 30000);

INSERT INTO Felhasznalo (felhasznalonev, email, jelszo, szerepkor)
VALUES ('Noemi', 'noemi@example.com', 'pw123', 'admin');
INSERT INTO Felhasznalo (felhasznalonev, email, jelszo, szerepkor)
VALUES ('Alice', 'alice@example.com', 'pw123', 'user');
INSERT INTO Felhasznalo (felhasznalonev, email, jelszo, szerepkor)
VALUES ('Bob', 'bob@example.com', 'pw123', 'user');
INSERT INTO Felhasznalo (felhasznalonev, email, jelszo, szerepkor)
VALUES ('Eve', 'eve@example.com', 'pw123', 'user');
INSERT INTO Felhasznalo (felhasznalonev, email, jelszo, szerepkor)
VALUES ('Carol', 'carol@example.com', 'pw123', 'user');

INSERT INTO Webtarhely (meret, statusz, letrehozas, felhasznalo_id)
VALUES (100, 'Aktív', SYSDATE, 1);
INSERT INTO Webtarhely (meret, statusz, letrehozas, felhasznalo_id)
VALUES (200, 'Aktív', SYSDATE, 2);
INSERT INTO Webtarhely (meret, statusz, letrehozas, felhasznalo_id)
VALUES (300, 'Inaktív', SYSDATE, 3);
INSERT INTO Webtarhely (meret, statusz, letrehozas, felhasznalo_id)
VALUES (400, 'Aktív', SYSDATE, 4);
INSERT INTO Webtarhely (meret, statusz, letrehozas, felhasznalo_id)
VALUES (500, 'Aktív', SYSDATE, 5);

INSERT INTO Reklam (szoveg, hivatkozas, felhasznalo_id)
VALUES ('Akciók!', 'https://reklam1.hu', 1);
INSERT INTO Reklam (szoveg, hivatkozas, felhasznalo_id)
VALUES ('Weboldal készítés', 'https://reklam2.hu', 2);
INSERT INTO Reklam (szoveg, hivatkozas, felhasznalo_id)
VALUES ('Domain vásár', 'https://reklam3.hu', 3);
INSERT INTO Reklam (szoveg, hivatkozas, felhasznalo_id)
VALUES ('Tárhely bővítés', 'https://reklam4.hu', 4);
INSERT INTO Reklam (szoveg, hivatkozas, felhasznalo_id)
VALUES ('Ingyenes SSL', 'https://reklam5.hu', 5);

INSERT INTO Rendelkezik (felhasznalo_id, dijcsomag_id) VALUES (1, 1);
INSERT INTO Rendelkezik (felhasznalo_id, dijcsomag_id) VALUES (2, 2);
INSERT INTO Rendelkezik (felhasznalo_id, dijcsomag_id) VALUES (3, 3);
INSERT INTO Rendelkezik (felhasznalo_id, dijcsomag_id) VALUES (4, 4);
INSERT INTO Rendelkezik (felhasznalo_id, dijcsomag_id) VALUES (5, 5);

INSERT INTO Szamla (osszeg, datum, allapot, rendelkezes_id) VALUES (5000, SYSDATE, 'Fizetett', 1);
INSERT INTO Szamla (osszeg, datum, allapot, rendelkezes_id) VALUES (10000, SYSDATE, 'Fizetett', 2);
INSERT INTO Szamla (osszeg, datum, allapot, rendelkezes_id) VALUES (15000, SYSDATE, 'Fizetett', 3);
INSERT INTO Szamla (osszeg, datum, allapot, rendelkezes_id) VALUES (20000, SYSDATE, 'Fizetett', 4);
INSERT INTO Szamla (osszeg, datum, allapot, rendelkezes_id) VALUES (30000, SYSDATE, 'Fizetett', 5);
