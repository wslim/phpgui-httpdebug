<?php
require 'src/autoload.php';

$icon = __DIR__ . '/assets/logo.ico';
$main = new HttpDebugMainFrame();
$main->SetIcon(new wxIcon($icon, wxBITMAP_TYPE_ICO));
$main->SetMinClientSize($main->GetMinSize());
$main->Show();
$main->Maximize();

wxEntry();