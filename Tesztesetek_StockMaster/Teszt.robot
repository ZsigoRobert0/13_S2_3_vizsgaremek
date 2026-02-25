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
