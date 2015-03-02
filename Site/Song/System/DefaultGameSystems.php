<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 2/22/2015
 * Time: 8:30 AM
 */
namespace Site\Song\System;



class DefaultGameSystems
{
    public function getDefaults() {
        return array(
//            "VES",
//            "Tracker",
            "Atari 2600",
            "Commodore 64",

//            "8-bit",
            "Nintendo",

//            "FM Synthesis",
            "Sega Master System",
            "Atari XEGS",
            "Atari 7800",

//            "16-bit",
            "Super Nintendo",

            "Turbo Grafx",
            "Sega Genesis",
            "NEO GEO",
            
            "Atari Jaguar",
            "3D0",
            "Sega 32X",
            "Sega Saturn",
            
            "PlayStation",
            "Nintendo 64",
            
            "Dreamcast",
            "PlayStation 2",
            "Game Cube",
            "XBOX",
            
            "XBOX 360",
            "PlayStation 3",
            "Wii",
            
            "Wii U",
            "PlayStation 4",
            "XBOX One",
        );
    }
}