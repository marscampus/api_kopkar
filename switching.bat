@echo off

echo ### ENV HAS BEEN SWITCHED ###
sleep 0.5

IF EXIST ".env" (
  ren online.env offline.env
)
  ren .env online.env

IF EXIST "offline.env" (
  ren offline.env .env 
)