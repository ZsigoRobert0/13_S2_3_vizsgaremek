*** Settings ***
Library           SeleniumLibrary

*** Test Cases ***
Belepes
    Open Browser    http://127.0.0.1:8000/legacy/StockMaster/login.php    firefox
    Maximize Browser Window
    Clear Element Text    id=username
    Input Text    id=username    demo
    Input Password    id=password    demo123
    Click Button    xpath=//button[contains(text(), 'Belépés')]

Regisztracio
    Open Browser    http://127.0.0.1:8000/legacy/StockMaster/login.php    firefox
    Maximize Browser Window
    Click Element    xpath=//a[@href='register.php']
    Sleep    1s
    Input Text    id=username    UjTesztFelho
    Input Text    id=email    teszt@pelda.hu
    Input Password    id=password    TitkosJelszo123!
    Input Password    id=password2    TitkosJelszo123!
    Click Button    xpath=//button[contains(text(), 'Regisztráció')]

Kijelentkezes
    Open Browser    http://127.0.0.1:8000/legacy/StockMaster/login.php    firefox
    Maximize Browser Window
    Clear Element Text    id=username
    Input Text    id=username    demo
    Input Password    id=password    demo123
    Click Button    xpath=//button[contains(text(), 'Belépés')]
    Sleep    2s
    Click Element    xpath=//a[@href='logout.php']
    Wait Until Page Contains    Bejelentkezés    timeout=5s

Statisztikak
    Open Browser    http://127.0.0.1:8000/legacy/StockMaster/login.php    firefox
    Maximize Browser Window
    Clear Element Text    id=username
    Input Text    id=username    demo
    Input Password    id=password    demo123
    Click Button    xpath=//button[contains(text(), 'Belépés')]
    Sleep    2s
    Click Element    xpath=//a[@href='stats.php']
    Sleep    1s

Tranzakcio
    Open Browser    http://127.0.0.1:8000/legacy/StockMaster/login.php    firefox
    Maximize Browser Window
    Clear Element Text    id=username
    Input Text    id=username    demo
    Input Password    id=password    demo123
    Click Button    xpath=//button[contains(text(), 'Belépés')]
    Sleep    2s
    Click Element    xpath=//a[@href='transactions.php']
    Sleep    1s

Beallitasok
    Open Browser    http://127.0.0.1:8000/legacy/StockMaster/login.php    firefox
    Maximize Browser Window
    Clear Element Text    id=username
    Input Text    id=username    demo
    Input Password    id=password    demo123
    Click Button    xpath=//button[contains(text(), 'Belépés')]
    Sleep    2s
    Click Element    xpath=//a[@href='settings.php']
    Sleep    1s

Vetel
    Open Browser    http://127.0.0.1:8000/legacy/StockMaster/login.php    firefox
    Maximize Browser Window
    Clear Element Text    id=username
    Input Text    id=username    demo
    Input Password    id=password    demo123
    Click Button    xpath=//button[contains(text(), 'Belépés')]
    Sleep    3s
    Clear Element Text    id=qty
    Input Text    id=qty    2
    Click Button    id=buyBtn
    Sleep    2s
    Wait Until Page Does Not Contain    Nincs nyitott pozíciód.    timeout=5s
    Wait Until Page Contains    AAPL    timeout=5s
    Wait Until Page Contains    2.00 db    timeout=5s

Eladas
    Open Browser    http://127.0.0.1:8000/legacy/StockMaster/login.php    chrome
    Maximize Browser Window
    Clear Element Text    id=username
    Input Text    id=username    demo
    Input Password    id=password    demo123
    Click Button    xpath=//button[contains(text(), 'Belépés')]
    Click Button    //*[@id="sellBtn"]
    Sleep    5s
    Wait Until Page Does Not Contain    Nincs nyitott pozíciód.
    Close Browser

Belepes_MasikAdat
    Open Browser    http://127.0.0.1:8000/legacy/StockMaster/login.php    firefox
    Maximize Browser Window
    Input Text    id=username    hibas_felhasznalo
    Input Text    id=password    hibas_jelszo
    Click Button    xpath=//button[contains(text(), 'Belépés')]
    Wait Until Page Contains    Hibás felhasználónév vagy jelszó.    timeout=5s
    Page Should Not Contain Element    id=kilepes-gomb
    [Teardown]    Close Browser

Regisztracio_HibasAdat
    Open Browser    http://127.0.0.1:8000/legacy/StockMaster/register.php    firefox
    Maximize Browser Window
    Sleep    3s
    Input Text    id=username    TesztFelhasznaloRossz
    Input Text    id=email    tesztRossz@teszt.hu
    Input Text    id=password    jelszo123
    Input Text    id=password2    mas_jelszo1
    Click Button    xpath=//button[contains(text(), 'Regisztráció')]    timeout=5s
    Wait Until Page Contains    A két jelszó nem egyezik!    timeout=5s
    Page Should Not Contain    Üdvözöllek
    [Teardown]    Close Browser
