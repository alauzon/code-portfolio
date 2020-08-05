#!/bin/bash
#
# This script is started from a foreign server to the website in order to precisely control the cron jobs around the
# feeds.
#

terminus=$(command -v terminus)
if [[ $terminus == "" ]];
then
  terminus=/usr/local/bin/terminus
fi
php=$(command -v php)
if [[ $php == "" ]];
then
  php=/usr/local/bin/php
fi
SITE=sample
ENV=$1
FEED=$2
RETRIES=10
TIME_BETWEEN_RETRIES=300
reg_connection_closed_error='^Connection to .* closed by remote'
reg_any_error='\[error\]'
tries=0

if [[ "$ENV" == "" || "$FEED" == "" ]];
then
  if [[ "$ENV" == "" ]];
  then
    echo "ENV is not set"
  fi
  if [[ "$FEED" == "" ]];
  then
    echo "FEED is not set"
  fi
  printf "Usage: ./sample_cron.sh env (Listings|Events)\nExample: ./sample_cron.sh live Listings\n"
  exit 2
fi

# Listings
if [[ "$FEED" == "Listings" ]];
then
  while [ 1 ]
  do
    $php $terminus remote:drush "$SITE.$ENV" -- asl Events    # Locks Events
    $php $terminus remote:drush "$SITE.$ENV" -- asu Listings  # Unlocks Listings
    $php $terminus remote:drush "$SITE.$ENV" -- assi Listings # Assigns Listings
    date
    errormessage=$(time $php $terminus remote:drush "$SITE.$ENV" -- cron 2>&1)
    errornum=$?
    echo "$errormessage"
    ((tries=tries+1))
    if [[ ($errormessage =~ $reg_connection_closed_error) ||
          ($errormessage =~ $reg_any_error) ||
          ($errornum != 0) ]];
    then
      echo 'Retrying fetching Listings...'
      if [[ $tries -ge $RETRIES ]];
      then
        echo "Fetching Listings failed after $tries tries."
        exit 1
      fi
      sleep $TIME_BETWEEN_RETRIES
      $php $terminus remote:drush "$SITE.$ENV" -- asu Listings
      $php $terminus remote:drush "$SITE.$ENV" -- assi Listings
    else
      break
    fi
  done

  echo "Successfully fetched Listings after $tries tries!"
  echo "Now parsing and processing Listings..."

  # Parsing and processing
  max_number_of_loops_to_do=10
  loops_done=0
  # Example of message to search for success: " [notice] Message: Updated 53 Listings."
  success_reg='( \[notice\] Message: Updated ([1-9][0-9]*) Listings.| \[notice\] Message: There are no new Listings.)'
  while [[ $loops_done -lt $max_number_of_loops_to_do ]]
  do
    errormessage=$(time $php $terminus remote:drush "$SITE.$ENV" -- cron 2>&1)
    errornum=$?
    echo "$errormessage"
    ((loops_done=loops_done+1))
    if [[ $errormessage =~ $success_reg ]];
    then
      echo "Parsing and processing Listings successful after $loops_done loops."
      $php $terminus remote:drush "$SITE.$ENV" -- asu Events    # Unlocks Events
      exit 0
    fi
  done

  echo "Parsing and processing Listings failed after $loops_done loops."
  exit 1
fi

# Events
if [[ "$FEED" == "Events" ]];
then
  # Example of message to search for success: " [notice] Message: Updated 53 Events."
  success_reg='( \[notice\] Message: Updated ([1-9][0-9]*) Events.| \[notice\] Message: There are no new Events.)'
  while [ 1 ]
  do
    $php $terminus remote:drush "$SITE.$ENV" -- asl Listings  # Locks Listings
    $php $terminus remote:drush "$SITE.$ENV" -- asu Events    # Unlocks Events
    $php $terminus remote:drush "$SITE.$ENV" -- assi Events   # Assigns Events
    date
    errormessage=$(time $php $terminus remote:drush "$SITE.$ENV" -- cron 2>&1)
    errornum=$?
    echo "$errormessage"
    ((tries=tries+1))
    if [[ $errormessage =~ $success_reg ]];
    then
      echo "Parsing and processing Events successful after first loop."
      $php $terminus remote:drush "$SITE.$ENV" -- asu Listings    # Unlocks Listings
      exit 0
    elif [[ ($errormessage =~ $reg_connection_closed_error) ||
          ($errormessage =~ $reg_any_error) ||
          ($errornum != 0) ]];
    then
      echo 'Retrying...'
      if [[ $tries -ge $RETRIES ]];
      then
        echo "Fetching Events failed after $tries tries."
        exit 1
      fi
      sleep $TIME_BETWEEN_RETRIES
      $php $terminus remote:drush "$SITE.$ENV" -- asu Events  # Unlocks Events
      $php $terminus remote:drush "$SITE.$ENV" -- assi Events # Assigns Events
    else
      break
    fi
  done

  echo "Now parsing and processing Events"

  # Parsing and processing
  max_number_of_loops_to_do=10
  loops_done=0
  while [[ $loops_done -lt $max_number_of_loops_to_do ]]
  do
    errormessage=$(time $php $terminus remote:drush "$SITE.$ENV" -- cron 2>&1)
    errornum=$?
    echo "$errormessage"
    ((loops_done=loops_done+1))
    if [[ $errormessage =~ $success_reg ]];
    then
      echo "Parsing and processing Events successful after $loops_done loops."
      $php $terminus remote:drush "$SITE.$ENV" -- asu Listings    # Unlocks Listings
      exit 0
    fi
  done

  echo "Parsing and processing Events failed after $loops_done loops."
  exit 1
fi

