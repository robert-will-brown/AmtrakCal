# AmtrakCal

         __        __________
        /  \         ========   _____________
         ||          =      =  / robert.will]
     ___==============      = /   .brown@   ]
     \_[            ========= [  gmail.com  ]
       [=====================^==============
    ___//_(_)_(_)_(_)___\__/_____(_)_(_)_(_)
    ========================================


This PHP script gives you a dynamically updating calendar appointment for the next train from your choosen origin and destination stations.  The appointment is the correct length for the duration of the journey (as reported by the amtrak website) and once the train has left, it will automatically show you the next journeys details.

Use this to subscribe to with your favourite iCal client and always see an appointment if your next available train.

This script has not been called with the correct arguments.  You need to set a destination and origin station in order to generate a usable calendar.

This example shows all the trains from San Diego to Solana Beach:
http://yourserver/youdirectory/amtrak.php?origin=SAN&destination=SOL

A list of the three letter station name abreviations can be found [here](http://en.wikipedia.org/wiki/List_of_Amtrak_stations#A)

