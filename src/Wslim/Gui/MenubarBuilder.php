<?php
namespace Wslim\Gui;

use wxMenuBar;
use wxMenu;
use wxMenuItem;

class MenubarBuilder
{
    /**
     * 
     * @var \wxWindow
     */
    private $window;
    
    /**
     * @var \wxMenuBar
     */
    private $menubar;
    
    /**
     * @var \wxMenuItem[]
     */
    private $menus;
    
    private $def = [
        'File' => [
            'items' => [
                'Quit' => [
                    'text'      => 'Quit',
                    'handler'   => 'CloseWindow',
                ],
            ]
        ],
        'Help' => [
            'items' => [
                'Home'    => [
                    'help'      => 'https://wslim.cn',
                    'handler'   => 'Home',
                ],
                //'-', // 或者是 ['-'],
                'About' => [
                    'handler'   => 'About',
                ],
                /*
                'Test'    => [
                    'items' => [
                        [
                            'handler'   => 'OpenUrl',
                        ]
                    ]
                ],
                */
            ]
        ],
    ];
    
    public function __construct($window=null, $def=null)
    {
        if ($def) {
            $this->def = array_merge($this->def, $def);
        }
        
        if ($window) $this->window = $window;
        
        $this->menubar = new wxMenuBar( 0 );
        
        $this->menus = $this->createMenus($this->def, $this->menubar);
        
    }
    
    public function getMenuBar()
    {
        return $this->menubar;
    }
    
    public function getMenus()
    {
        return $this->menus;
    }
    
    /**
     * 
     * @param  array $items
     * @param  \wxControl $parent
     * @return \wxMenuItem[]
     */
    public function createMenus($items, $parent=null)
    {
        $parent = $parent ? : $this->menubar;
        $menus = [];
        foreach ($items as $k => $v) {
            if (is_string($v)) {
                if ($v == '-' ) {
                    $v = ['type' => 'SEPARATOR', 'text'=>'-'];
                } else {
                    $v = ['text' => $v];
                }
            } else {
                if (isset($v[0]) && $v[0] == '-') {
                    $v = ['type' => 'SEPARATOR', 'text'=>'-'];
                } elseif (!isset($v['text'])) {
                    $v['text'] = is_numeric($k) ? 'menu' : $k; 
                }
            }
            
            if (isset($v['items'])) {
                $menu = new wxMenu();
                $items = $this->createMenus($v['items'], $menu);
                
                if ($parent instanceof \wxMenu) {
                    $parent->AppendSubMenu($menu, $v['text']);
                } else {
                    $parent->Append($menu, $v['text']);
                }
                
                $menus[] = $menu; 
            } else {
                if (!$parent instanceof \wxMenu) {
                    $menu = new wxMenu();
                    $vv = [
                        [$k => $v]
                    ];
                    
                    $items = $this->createMenus($vv, $menu);
                    
                    $parent->Append($menu, $v['text']);
                    
                    $menus[] = $menu; 
                } else {
                    $mtype = isset($v['type']) ? strtoupper($v['type']) : 'NORMAL';
                    if (!in_array($mtype, ['NORMAL', 'CHECKBOX', 'RADIO', 'SEPARATOR', 'DROPDOWN'])) {
                        $mtype = 'NORMAL';
                    }
                    $mkind = "wxITEM_$mtype";
                    
                    if ($mtype === 'SEPARATOR') {
                        $parent->AppendSeparator();
                    } else {
                        $helpStr = isset($v['help']) ? $v['help'] : wxEmptyString;
                        $item = new wxMenuItem($parent, wxID_ANY, $v['text'], $helpStr, get_defined_constants()[$mkind]);
                        $parent->Append( $item );
                        
                        if (isset($v['handler']) && $v['handler']) {
                            if ($callable = $this->parseCallable($v['handler'])) {
                                $this->menubar->Connect($item->GetId(), wxEVT_COMMAND_MENU_SELECTED, $callable);
                            }
                        }
                    }
                    
                    //$menus[] = $item;
                }
            }
        }
        return $menus;
    }
    
    public function createMenuItems($parent, $def)
    {
        
    }
    
    private function parseCallable($callable)
    {
        if (!is_callable($callable)) {
            if (is_string($callable)) {
                $callable = ucfirst($callable);
                if ($this->window && method_exists($this->window, $callable)) {
                    return [$this->window, $callable];
                } elseif (method_exists($this, $callable)) {
                    return [$this, $callable];
                }
            }
            return null;
        }
        return $callable;
    }
    
    public function About()
    {
        $dial = new \wxMessageDialog(NULL, "author: gwbnet@126.com\r\n\r\nlink: http://wslim.cn", "Info", wxOK);
        $dial->ShowModal();
    }
    
    public function Home()
    {
        wxLaunchDefaultBrowser('https://wslim.cn');
    }
}