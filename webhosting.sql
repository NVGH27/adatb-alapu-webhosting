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

CREATE OR REPLACE TRIGGER trg_szamla_letrehozas
AFTER INSERT ON Rendelkezik
FOR EACH ROW
DECLARE
v_ar NUMBER;
BEGIN
SELECT ar INTO v_ar
FROM Dijcsomag
WHERE dijcsomag_id = :NEW.dijcsomag_id;

INSERT INTO Szamla (szamla_id, osszeg, datum, allapot, rendelkezes_id)
VALUES (
           szamla_id_seq.NEXTVAL,
           v_ar,
           SYSDATE,
           'Függőben',
           :NEW.rendelkezes_id
       );
END;
/

CREATE OR REPLACE TRIGGER trg_szamla_allapot_valtozas
AFTER UPDATE ON Szamla
                 FOR EACH ROW
BEGIN
    IF :NEW.allapot = 'Fizetve' THEN
UPDATE Webtarhely
SET statusz = 'Aktív'
WHERE webtarhely_id = (
    SELECT webtarhely_id
    FROM Rendelkezik
    WHERE rendelkezes_id = :NEW.rendelkezes_id
);
END IF;
END;
/

CREATE OR REPLACE PROCEDURE fizetesi_hatarido_ellenorzes IS
BEGIN
FOR sz IN (
        SELECT s.rendelkezes_id, r.felhasznalo_id
        FROM Szamla s
        JOIN Rendelkezik r ON s.rendelkezes_id = r.rendelkezes_id
        WHERE s.allapot IN ('Függőben')
    ) LOOP
UPDATE Webtarhely
SET statusz = 'Inaktív'
WHERE felhasznalo_id = sz.felhasznalo_id;
UPDATE Szamla
SET allapot = 'Késedelmes'
WHERE rendelkezes_id = sz.rendelkezes_id;
END LOOP;
END;
/

CREATE OR REPLACE FUNCTION felhasznalo_havi_kiadas(p_felhasznalo_id IN NUMBER)
RETURN NUMBER IS
  v_osszeg NUMBER := 0;
BEGIN
SELECT NVL(SUM(sz.osszeg), 0)
INTO v_osszeg
FROM Szamla sz
         JOIN Rendelkezik r ON sz.rendelkezes_id = r.rendelkezes_id
WHERE r.felhasznalo_id = p_felhasznalo_id
  AND TO_CHAR(sz.datum, 'MMYYYY') = TO_CHAR(SYSDATE, 'MMYYYY');
RETURN v_osszeg;
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
INSERT INTO Felhasznalo (felhasznalonev, email, jelszo, szerepkor)
VALUES ('Kovács Péter', 'kovacs.peter@example.com', 'pw123', 'admin');
INSERT INTO Felhasznalo (felhasznalonev, email, jelszo, szerepkor)
VALUES ('Nagy Anna', 'nagy.anna@example.com', 'pw123', 'user');
INSERT INTO Felhasznalo (felhasznalonev, email, jelszo, szerepkor)
VALUES ('Szabó Gábor', 'szabo.gabor@example.com', 'pw123', 'user');
INSERT INTO Felhasznalo (felhasznalonev, email, jelszo, szerepkor)
VALUES ('Tóth Eszter', 'toth.eszter@example.com', 'pw123', 'user');
INSERT INTO Felhasznalo (felhasznalonev, email, jelszo, szerepkor)
VALUES ('Horváth László', 'horvath.laszlo@example.com', 'pw123', 'user');
INSERT INTO Felhasznalo (felhasznalonev, email, jelszo, szerepkor)
VALUES ('Farkas Júlia', 'farkas.julia@example.com', 'pw123', 'user');
INSERT INTO Felhasznalo (felhasznalonev, email, jelszo, szerepkor)
VALUES ('Varga Dávid', 'varga.david@example.com', 'pw123', 'user');
INSERT INTO Felhasznalo (felhasznalonev, email, jelszo, szerepkor)
VALUES ('Balogh Katalin', 'balogh.katalin@example.com', 'pw123', 'user');
INSERT INTO Felhasznalo (felhasznalonev, email, jelszo, szerepkor)
VALUES ('Papp Zoltán', 'papp.zoltan@example.com', 'pw123', 'user');
INSERT INTO Felhasznalo (felhasznalonev, email, jelszo, szerepkor)
VALUES ('Kiss Éva', 'kiss.eva@example.com', 'pw123', 'user');
INSERT INTO Felhasznalo (felhasznalonev, email, jelszo, szerepkor)
VALUES ('Molnár Attila', 'molnar.attila@example.com', 'pw123', 'user');
INSERT INTO Felhasznalo (felhasznalonev, email, jelszo, szerepkor)
VALUES ('Kerekes Andrea', 'kerekes.andrea@example.com', 'pw123', 'user');
INSERT INTO Felhasznalo (felhasznalonev, email, jelszo, szerepkor)
VALUES ('Németh Tamás', 'nemeth.tamas@example.com', 'pw123', 'user');
INSERT INTO Felhasznalo (felhasznalonev, email, jelszo, szerepkor)
VALUES ('Szalai Lilla', 'szalai.lilla@example.com', 'pw123', 'user');
INSERT INTO Felhasznalo (felhasznalonev, email, jelszo, szerepkor)
VALUES ('Fülöp Gergő', 'fulop.gergo@example.com', 'pw123', 'user');
INSERT INTO Felhasznalo (felhasznalonev, email, jelszo, szerepkor)
VALUES ('Benedek Erika', 'benedek.erika@example.com', 'pw123', 'user');
INSERT INTO Felhasznalo (felhasznalonev, email, jelszo, szerepkor)
VALUES ('Szőke Roland', 'szoke.roland@example.com', 'pw123', 'user');
INSERT INTO Felhasznalo (felhasznalonev, email, jelszo, szerepkor)
VALUES ('Major Vivien', 'major.vivien@example.com', 'pw123', 'user');
INSERT INTO Felhasznalo (felhasznalonev, email, jelszo, szerepkor)
VALUES ('Vincze Norbert', 'vincze.norbert@example.com', 'pw123', 'user');
INSERT INTO Felhasznalo (felhasznalonev, email, jelszo, szerepkor)
VALUES ('Horváth Anna', 'horvath.anna@example.com', 'pw123', 'user');
INSERT INTO Felhasznalo (felhasznalonev, email, jelszo, szerepkor)
VALUES ('Kiss Péter', 'kiss.peter@example.com', 'pw123', 'user');
INSERT INTO Felhasznalo (felhasznalonev, email, jelszo, szerepkor)
VALUES ('Nagy Béla', 'nagy.bela@example.com', 'pw123', 'user');
INSERT INTO Felhasznalo (felhasznalonev, email, jelszo, szerepkor)
VALUES ('Varga Éva', 'varga.eva@example.com', 'pw123', 'user');
INSERT INTO Felhasznalo (felhasznalonev, email, jelszo, szerepkor)
VALUES ('Tóth Gábor', 'toth.gabor@example.com', 'pw123', 'user');
INSERT INTO Felhasznalo (felhasznalonev, email, jelszo, szerepkor)
VALUES ('Fekete Anna', 'fekete.anna@example.com', 'pw123', 'admin');

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
INSERT INTO Webtarhely (meret, statusz, letrehozas, felhasznalo_id)
VALUES (200, 'Inaktív', SYSDATE, 6);
INSERT INTO Webtarhely (meret, statusz, letrehozas, felhasznalo_id)
VALUES (220, 'Aktív', SYSDATE, 7);
INSERT INTO Webtarhely (meret, statusz, letrehozas, felhasznalo_id)
VALUES (240, 'Inaktív', SYSDATE, 8);
INSERT INTO Webtarhely (meret, statusz, letrehozas, felhasznalo_id)
VALUES (260, 'Aktív', SYSDATE, 9);
INSERT INTO Webtarhely (meret, statusz, letrehozas, felhasznalo_id)
VALUES (280, 'Inaktív', SYSDATE, 10);
INSERT INTO Webtarhely (meret, statusz, letrehozas, felhasznalo_id)
VALUES (300, 'Aktív', SYSDATE, 11);
INSERT INTO Webtarhely (meret, statusz, letrehozas, felhasznalo_id)
VALUES (320, 'Inaktív', SYSDATE, 12);
INSERT INTO Webtarhely (meret, statusz, letrehozas, felhasznalo_id)
VALUES (340, 'Aktív', SYSDATE, 13);
INSERT INTO Webtarhely (meret, statusz, letrehozas, felhasznalo_id)
VALUES (360, 'Inaktív', SYSDATE, 14);
INSERT INTO Webtarhely (meret, statusz, letrehozas, felhasznalo_id)
VALUES (380, 'Aktív', SYSDATE, 15);
INSERT INTO Webtarhely (meret, statusz, letrehozas, felhasznalo_id)
VALUES (400, 'Inaktív', SYSDATE, 16);
INSERT INTO Webtarhely (meret, statusz, letrehozas, felhasznalo_id)
VALUES (420, 'Aktív', SYSDATE, 17);
INSERT INTO Webtarhely (meret, statusz, letrehozas, felhasznalo_id)
VALUES (440, 'Inaktív', SYSDATE, 18);
INSERT INTO Webtarhely (meret, statusz, letrehozas, felhasznalo_id)
VALUES (460, 'Aktív', SYSDATE, 19);
INSERT INTO Webtarhely (meret, statusz, letrehozas, felhasznalo_id)
VALUES (480, 'Inaktív', SYSDATE, 20);
INSERT INTO Webtarhely (meret, statusz, letrehozas, felhasznalo_id)
VALUES (500, 'Aktív', SYSDATE, 21);
INSERT INTO Webtarhely (meret, statusz, letrehozas, felhasznalo_id)
VALUES (600, 'Inaktív', SYSDATE, 22);
INSERT INTO Webtarhely (meret, statusz, letrehozas, felhasznalo_id)
VALUES (700, 'Aktív', SYSDATE, 23);
INSERT INTO Webtarhely (meret, statusz, letrehozas, felhasznalo_id)
VALUES (800, 'Inaktív', SYSDATE, 24);
INSERT INTO Webtarhely (meret, statusz, letrehozas, felhasznalo_id)
VALUES (900, 'Aktív', SYSDATE, 25);
INSERT INTO Webtarhely (meret, statusz, letrehozas, felhasznalo_id)
VALUES (1000, 'Inaktív', SYSDATE, 26);
INSERT INTO Webtarhely (meret, statusz, letrehozas, felhasznalo_id)
VALUES (1100, 'Aktív', SYSDATE, 27);
INSERT INTO Webtarhely (meret, statusz, letrehozas, felhasznalo_id)
VALUES (1200, 'Inaktív', SYSDATE, 28);
INSERT INTO Webtarhely (meret, statusz, letrehozas, felhasznalo_id)
VALUES (1300, 'Aktív', SYSDATE, 29);
INSERT INTO Webtarhely (meret, statusz, letrehozas, felhasznalo_id)
VALUES (1400, 'Inaktív', SYSDATE, 30);

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
INSERT INTO Reklam (szoveg, hivatkozas, felhasznalo_id) VALUES ('Gyors szerverek', 'https://reklam6.hu', 6);
INSERT INTO Reklam (szoveg, hivatkozas, felhasznalo_id) VALUES ('Kedvezményes csomagok', 'https://reklam7.hu', 7);
INSERT INTO Reklam (szoveg, hivatkozas, felhasznalo_id) VALUES ('24/7 ügyfélszolgálat', 'https://reklam8.hu', 8);
INSERT INTO Reklam (szoveg, hivatkozas, felhasznalo_id) VALUES ('Ingyenes domain', 'https://reklam9.hu', 9);
INSERT INTO Reklam (szoveg, hivatkozas, felhasznalo_id) VALUES ('Prémium támogatás', 'https://reklam10.hu', 10);
INSERT INTO Reklam (szoveg, hivatkozas, felhasznalo_id) VALUES ('Webshop megoldások', 'https://reklam11.hu', 11);
INSERT INTO Reklam (szoveg, hivatkozas, felhasznalo_id) VALUES ('Professzionális email', 'https://reklam12.hu', 12);
INSERT INTO Reklam (szoveg, hivatkozas, felhasznalo_id) VALUES ('Reszponzív dizájn', 'https://reklam13.hu', 13);
INSERT INTO Reklam (szoveg, hivatkozas, felhasznalo_id) VALUES ('Adatmentés', 'https://reklam14.hu', 14);
INSERT INTO Reklam (szoveg, hivatkozas, felhasznalo_id) VALUES ('Biztonságos fizetés', 'https://reklam15.hu', 15);


INSERT INTO Rendelkezik (felhasznalo_id, dijcsomag_id) VALUES (1, 1);
INSERT INTO Rendelkezik (felhasznalo_id, dijcsomag_id) VALUES (2, 2);
INSERT INTO Rendelkezik (felhasznalo_id, dijcsomag_id) VALUES (3, 3);
INSERT INTO Rendelkezik (felhasznalo_id, dijcsomag_id) VALUES (4, 4);
INSERT INTO Rendelkezik (felhasznalo_id, dijcsomag_id) VALUES (5, 5);
INSERT INTO Rendelkezik (felhasznalo_id, dijcsomag_id) VALUES (1, 1);
INSERT INTO Rendelkezik (felhasznalo_id, dijcsomag_id) VALUES (2, 2);
INSERT INTO Rendelkezik (felhasznalo_id, dijcsomag_id) VALUES (3, 3);
INSERT INTO Rendelkezik (felhasznalo_id, dijcsomag_id) VALUES (4, 4);
INSERT INTO Rendelkezik (felhasznalo_id, dijcsomag_id) VALUES (5, 5);
INSERT INTO Rendelkezik (felhasznalo_id, dijcsomag_id) VALUES (6, 1);
INSERT INTO Rendelkezik (felhasznalo_id, dijcsomag_id) VALUES (7, 2);
INSERT INTO Rendelkezik (felhasznalo_id, dijcsomag_id) VALUES (8, 3);
INSERT INTO Rendelkezik (felhasznalo_id, dijcsomag_id) VALUES (9, 4);
INSERT INTO Rendelkezik (felhasznalo_id, dijcsomag_id) VALUES (10, 5);
INSERT INTO Rendelkezik (felhasznalo_id, dijcsomag_id) VALUES (11, 1);
INSERT INTO Rendelkezik (felhasznalo_id, dijcsomag_id) VALUES (12, 2);
INSERT INTO Rendelkezik (felhasznalo_id, dijcsomag_id) VALUES (13, 3);
INSERT INTO Rendelkezik (felhasznalo_id, dijcsomag_id) VALUES (14, 4);
INSERT INTO Rendelkezik (felhasznalo_id, dijcsomag_id) VALUES (15, 5);
INSERT INTO Rendelkezik (felhasznalo_id, dijcsomag_id) VALUES (16, 1);
INSERT INTO Rendelkezik (felhasznalo_id, dijcsomag_id) VALUES (17, 2);
INSERT INTO Rendelkezik (felhasznalo_id, dijcsomag_id) VALUES (18, 3);
INSERT INTO Rendelkezik (felhasznalo_id, dijcsomag_id) VALUES (19, 4);
INSERT INTO Rendelkezik (felhasznalo_id, dijcsomag_id) VALUES (20, 5);
INSERT INTO Rendelkezik (felhasznalo_id, dijcsomag_id) VALUES (21, 1);
INSERT INTO Rendelkezik (felhasznalo_id, dijcsomag_id) VALUES (22, 2);
INSERT INTO Rendelkezik (felhasznalo_id, dijcsomag_id) VALUES (23, 3);
INSERT INTO Rendelkezik (felhasznalo_id, dijcsomag_id) VALUES (24, 4);
INSERT INTO Rendelkezik (felhasznalo_id, dijcsomag_id) VALUES (25, 5);
INSERT INTO Rendelkezik (felhasznalo_id, dijcsomag_id) VALUES (26, 1);
INSERT INTO Rendelkezik (felhasznalo_id, dijcsomag_id) VALUES (27, 2);
INSERT INTO Rendelkezik (felhasznalo_id, dijcsomag_id) VALUES (28, 3);
INSERT INTO Rendelkezik (felhasznalo_id, dijcsomag_id) VALUES (29, 4);
INSERT INTO Rendelkezik (felhasznalo_id, dijcsomag_id) VALUES (30, 5);

INSERT INTO Szamla (osszeg, datum, allapot, rendelkezes_id) VALUES (5000, SYSDATE, 'Fizetett', 1);
INSERT INTO Szamla (osszeg, datum, allapot, rendelkezes_id) VALUES (10000, SYSDATE, 'Fizetett', 2);
INSERT INTO Szamla (osszeg, datum, allapot, rendelkezes_id) VALUES (15000, SYSDATE, 'Fizetett', 3);
INSERT INTO Szamla (osszeg, datum, allapot, rendelkezes_id) VALUES (20000, SYSDATE, 'Fizetett', 4);
INSERT INTO Szamla (osszeg, datum, allapot, rendelkezes_id) VALUES (30000, SYSDATE, 'Fizetett', 5);
INSERT INTO Szamla (osszeg, datum, allapot, rendelkezes_id) VALUES (5000, SYSDATE, 'Függőben', 6);
INSERT INTO Szamla (osszeg, datum, allapot, rendelkezes_id) VALUES (10000, SYSDATE, 'Függőben', 7);
INSERT INTO Szamla (osszeg, datum, allapot, rendelkezes_id) VALUES (15000, SYSDATE, 'Függőben', 8);
INSERT INTO Szamla (osszeg, datum, allapot, rendelkezes_id) VALUES (20000, SYSDATE, 'Függőben', 9);
INSERT INTO Szamla (osszeg, datum, allapot, rendelkezes_id) VALUES (30000, SYSDATE, 'Függőben', 10);
INSERT INTO Szamla (osszeg, datum, allapot, rendelkezes_id) VALUES (5000, SYSDATE, 'Fizetett', 11);
INSERT INTO Szamla (osszeg, datum, allapot, rendelkezes_id) VALUES (10000, SYSDATE, 'Fizetett', 12);
INSERT INTO Szamla (osszeg, datum, allapot, rendelkezes_id) VALUES (15000, SYSDATE, 'Fizetett', 13);
INSERT INTO Szamla (osszeg, datum, allapot, rendelkezes_id) VALUES (20000, SYSDATE, 'Fizetett', 14);
INSERT INTO Szamla (osszeg, datum, allapot, rendelkezes_id) VALUES (30000, SYSDATE, 'Fizetett', 15);
INSERT INTO Szamla (osszeg, datum, allapot, rendelkezes_id) VALUES (5000, SYSDATE, 'Fizetett', 16);
INSERT INTO Szamla (osszeg, datum, allapot, rendelkezes_id) VALUES (10000, SYSDATE, 'Fizetett', 17);
INSERT INTO Szamla (osszeg, datum, allapot, rendelkezes_id) VALUES (15000, SYSDATE, 'Fizetett', 18);
INSERT INTO Szamla (osszeg, datum, allapot, rendelkezes_id) VALUES (20000, SYSDATE, 'Fizetett', 19);
INSERT INTO Szamla (osszeg, datum, allapot, rendelkezes_id) VALUES (30000, SYSDATE, 'Fizetett', 20);
INSERT INTO Szamla (osszeg, datum, allapot, rendelkezes_id) VALUES (5000, SYSDATE, 'Függőben', 26);
INSERT INTO Szamla (osszeg, datum, allapot, rendelkezes_id) VALUES (10000, SYSDATE, 'Függőben', 27);
INSERT INTO Szamla (osszeg, datum, allapot, rendelkezes_id) VALUES (15000, SYSDATE, 'Függőben', 28);
INSERT INTO Szamla (osszeg, datum, allapot, rendelkezes_id) VALUES (20000, SYSDATE, 'Függőben', 29);
INSERT INTO Szamla (osszeg, datum, allapot, rendelkezes_id) VALUES (30000, SYSDATE, 'Függőben', 30);
INSERT INTO Szamla (osszeg, datum, allapot, rendelkezes_id) VALUES (5000, SYSDATE, 'Fizetett', 31);

INSERT INTO Domain (domain_nev, domain_tipus, lejarati_datum, webtarhely_id) VALUES ('domain1.hu', '.hu', ADD_MONTHS(SYSDATE, 12), 1);
INSERT INTO Domain (domain_nev, domain_tipus, lejarati_datum, webtarhely_id) VALUES ('domain2.hu', '.hu', ADD_MONTHS(SYSDATE, 12), 2);
INSERT INTO Domain (domain_nev, domain_tipus, lejarati_datum, webtarhely_id) VALUES ('domain3.hu', '.hu', ADD_MONTHS(SYSDATE, 12), 3);
INSERT INTO Domain (domain_nev, domain_tipus, lejarati_datum, webtarhely_id) VALUES ('domain4.hu', '.hu', ADD_MONTHS(SYSDATE, 12), 4);
INSERT INTO Domain (domain_nev, domain_tipus, lejarati_datum, webtarhely_id) VALUES ('domain5.hu', '.hu', ADD_MONTHS(SYSDATE, 12), 5);
INSERT INTO Domain (domain_nev, domain_tipus, lejarati_datum, webtarhely_id) VALUES ('domain6.hu', '.hu', ADD_MONTHS(SYSDATE, 12), 6);
INSERT INTO Domain (domain_nev, domain_tipus, lejarati_datum, webtarhely_id) VALUES ('domain7.hu', '.hu', ADD_MONTHS(SYSDATE, 12), 7);
INSERT INTO Domain (domain_nev, domain_tipus, lejarati_datum, webtarhely_id) VALUES ('domain8.hu', '.hu', ADD_MONTHS(SYSDATE, 12), 8);
INSERT INTO Domain (domain_nev, domain_tipus, lejarati_datum, webtarhely_id) VALUES ('domain9.hu', '.hu', ADD_MONTHS(SYSDATE, 12), 9);
INSERT INTO Domain (domain_nev, domain_tipus, lejarati_datum, webtarhely_id) VALUES ('domain10.hu', '.hu', ADD_MONTHS(SYSDATE, 12), 10);
INSERT INTO Domain (domain_nev, domain_tipus, lejarati_datum, webtarhely_id) VALUES ('domain11.hu', '.hu', ADD_MONTHS(SYSDATE, 12), 11);
INSERT INTO Domain (domain_nev, domain_tipus, lejarati_datum, webtarhely_id) VALUES ('domain12.hu', '.hu', ADD_MONTHS(SYSDATE, 12), 12);
INSERT INTO Domain (domain_nev, domain_tipus, lejarati_datum, webtarhely_id) VALUES ('domain13.hu', '.hu', ADD_MONTHS(SYSDATE, 12), 13);
INSERT INTO Domain (domain_nev, domain_tipus, lejarati_datum, webtarhely_id) VALUES ('domain14.hu', '.hu', ADD_MONTHS(SYSDATE, 12), 14);
INSERT INTO Domain (domain_nev, domain_tipus, lejarati_datum, webtarhely_id) VALUES ('domain15.hu', '.hu', ADD_MONTHS(SYSDATE, 12), 15);
INSERT INTO Domain (domain_nev, domain_tipus, lejarati_datum, webtarhely_id) VALUES ('domain16.hu', '.hu', ADD_MONTHS(SYSDATE, 12), 16);
INSERT INTO Domain (domain_nev, domain_tipus, lejarati_datum, webtarhely_id) VALUES ('domain17.hu', '.hu', ADD_MONTHS(SYSDATE, 12), 17);
INSERT INTO Domain (domain_nev, domain_tipus, lejarati_datum, webtarhely_id) VALUES ('domain18.hu', '.hu', ADD_MONTHS(SYSDATE, 12), 18);
INSERT INTO Domain (domain_nev, domain_tipus, lejarati_datum, webtarhely_id) VALUES ('domain19.hu', '.hu', ADD_MONTHS(SYSDATE, 12), 19);
