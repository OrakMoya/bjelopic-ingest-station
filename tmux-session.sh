#!/bin/bash

session="bjelopic-ingest-station"

    tmux new-session -d -s $session

    #Editor 1
    tmux rename-window -t 1 'Editor'
    tmux send-keys -t $session:1 'nvim .' C-m

    #Terminal 2
    tmux new-window -t $session:2 -n 'Terminal'

    #Artisan server 3
    tmux new-window -t $session:3 -n 'Artisan serve'
    tmux send-keys -t $session:3 'XDEBUG_SESSION=1 php artisan serve' C-m

    #Reverb server 4
    tmux new-window -t $session:4 -n 'Reverb'
    tmux send-keys -t $session:4 'php artisan reverb:start' C-m

    #Node dev server 5
    tmux new-window -t $session:5 -n 'Npm dev'
    tmux send-keys -t $session:5 'npm run dev' C-m

tmux attach-session -t $session:1
