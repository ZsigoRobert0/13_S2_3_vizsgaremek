*** Settings ***
Library           SeleniumLibrary

*** Test Cases ***
Regisztracio
    Open Browser    http://127.0.0.1:8000/legacy/StockMaster/login.php    firefox
    Maximize Browser Window
    Clear Element Text    id=username
    Input Text    id=username    demo
    Input Password    id=password    demo123
    Click Button    xpath=//button[contains(text(), 'Belépés')]
