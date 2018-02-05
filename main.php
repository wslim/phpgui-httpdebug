<?php
require 'src/autoload.php';

$main = new HttpDebugMainFrame();
$main->SetMinClientSize($main->GetMinSize());
$main->Show();
$main->Maximize();

wxEntry();