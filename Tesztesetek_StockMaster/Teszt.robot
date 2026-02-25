*** Settings ***
Library           SeleniumLibrary

*** Test Cases ***
Belepes
    Open Browser    http://127.0.0.1:8000/legacy/StockMaster/login.php    firefox
    Maximize Browser Window
    Clear Element Text    id=username
    Input Text    id=username    dem
    Input Password    id=password demo12
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
