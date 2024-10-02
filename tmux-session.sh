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

    #laravel jobs 5
    tmux new-windo -t $session:5 -n 'Scheduler'
    tmux send-keys -t $session:5 'php artisan schedule:work' C-m

    #Node dev server 6
    tmux new-window -t $session:6 -n 'Npm dev'
    tmux send-keys -t $session:6 'npm run dev' C-m

    #Queue worker
    tmux new-window -t $session:7 -n 'Queue'
    tmux send-keys -t $session:7 'php artisan queue:listen -v --timeout 600' C-m

tmux attach-session -t $session:1
