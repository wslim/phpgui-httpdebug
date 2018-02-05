<?php
use Wslim\Util\HttpRequest;
use Wslim\Util\JsonHelper;
use Wslim\Gui\MenubarBuilder;

class HttpDebugMainFrame extends wxFrame
{
    private $menubar;
    
    private $panel;
    private $ctl_url;
    private $ctl_method;
    private $opt_use_header;
    private $ctl_req_query;
    private $ctl_req_cookie;
    private $ctl_req_header;
    private $ctl_res_header;
    private $txt_res_body;
    private $wvw_res_body;
    private $txt_res_json;
    
    public function __construct()
    {
        // =========== configuration ===============
        $title = "HttpDebug";
        $width = 640;
        $height = 480;
        $icon = realpath("./assets/logo.jpg");
        
        // =========================================
        parent::__construct(null, wxID_TOP, $title, wxDefaultPosition, new wxSize($width, $height) );
        wxInitAllImageHandlers();
        $this->SetIcon(new wxIcon($icon, wxBITMAP_TYPE_JPEG));
        $this->SetBackgroundColour(new wxColour(255,255,255));
        
        $this->_init();
        
        // menubar
        $menuBarBuilder = new MenubarBuilder($this);
        $this->SetMenuBar($menuBarBuilder->getMenuBar());
    }
    
    function _init()
    {
        $dwidth = 70;
        
        // 使用一个panel包围所有控件，而不是使用frame自身
        $this->panel = $panel = new wxPanel($this, wxID_ANY);
        
        // box1: url/get/start
        $url_label  = new wxStaticText($panel, wxID_ANY, 'Url:', wxDefaultPosition, new wxSize($dwidth, 30));
        $this->ctl_url   = new wxTextCtrl($panel, wxID_ANY, "https://wslim.cn", wxDefaultPosition);
        $this->ctl_method = new wxChoice($panel, wxID_ANY, wxDefaultPosition, new wxSize($dwidth, 30), HttpRequest::METHODS);
        $this->ctl_method->SetSelection(0);
        $start_button = new wxButton($panel, wxID_ANY, "start", wxDefaultPosition, new wxSize(100, 26), 0 );
        $start_button->Connect(wxEVT_COMMAND_BUTTON_CLICKED, array($this, 'start_click') );

        $box11  = new wxBoxSizer(wxHORIZONTAL);
        $box11->Add($url_label, 0, wxLEFT);
        $box11->Add($this->ctl_url, 1, wxLEFT, 2);
        $box11->Add($this->ctl_method, 0, wxLEFT, 2);
        $box11->Add($start_button, 0, wxLEFT, 2);
        
        // box12: options
        $lbl_option  = new wxStaticText($panel, wxID_ANY, 'Options:', wxDefaultPosition, new wxSize($dwidth, 30));
        $this->opt_use_header = new wxCheckBox($panel, wxID_ANY, '发送header');
        $box12 = new wxBoxSizer(wxHORIZONTAL);
        $box12->Add($lbl_option, 0, wxLEFT);
        $box12->Add($this->opt_use_header, 1, wxLEFT, 2);
        
        // box21: request query/cookie/header
        $lbl_req_query  = new wxStaticText($panel, wxID_ANY, "Request\r\nData:", wxDefaultPosition, new wxSize($dwidth, 30));
        $this->ctl_req_query = new wxTextCtrl($panel, wxID_ANY, "", wxDefaultPosition, new wxSize(100, 100), wxTE_MULTILINE );
        
        $lbl_cookie  = new wxStaticText($panel, wxID_ANY, "Request\r\nCookie:", wxDefaultPosition, new wxSize($dwidth, 30));
        $this->ctl_req_cookie = new wxTextCtrl($panel, wxID_ANY, "", wxDefaultPosition, new wxSize(100, 100), wxTE_MULTILINE );
        
        $lbl_req_header  = new wxStaticText($panel, wxID_ANY, "Request\r\nHeaders:", wxDefaultPosition, new wxSize($dwidth, 30));
        $this->ctl_req_header = new wxTextCtrl($panel, wxID_ANY, "", wxDefaultPosition, new wxSize(100, 100), wxTE_MULTILINE );
        
        $lbl_res_header  = new wxStaticText($panel, wxID_ANY, "Response\r\nHeaders:", wxDefaultPosition, new wxSize($dwidth, 30));
        $this->ctl_res_header = new wxTextCtrl($panel, wxID_ANY, "", wxDefaultPosition, new wxSize(100, 100), wxTE_MULTILINE );
        
        // query
        $box2111 = new wxBoxSizer(wxHORIZONTAL);
        $box2111->Add($lbl_req_query, 0, wxLEFT, 0);
        $box2111->Add($this->ctl_req_query, 1, wxLEFT, 0);
        // cookie
        $box2112 = new wxBoxSizer(wxHORIZONTAL);
        $box2112->Add($lbl_cookie, 0, wxRIGHT, 0);
        $box2112->Add($this->ctl_req_cookie, 1, wxRIGHT);
        
        $box211  = new wxBoxSizer(wxVERTICAL);
        $box211->Add($box2111, 0, wxEXPAND | wxBOTTOM, 2);
        $box211->Add($box2112, 0, wxEXPAND | wxTop, 2); // 空白没起作用？
        
        // req header
        $box2121 = new wxBoxSizer(wxHORIZONTAL);
        $box2121->Add($lbl_req_header, 0, wxRIGHT, 0);
        $box2121->Add($this->ctl_req_header, 1, wxRIGHT, 0);
        // res header
        $box2122 = new wxBoxSizer(wxHORIZONTAL);
        $box2122->Add($lbl_res_header, 0, wxRIGHT, 0);
        $box2122->Add($this->ctl_res_header, 1, wxRIGHT);
        
        $box212  = new wxBoxSizer(wxVERTICAL);
        $box212->Add($box2121, 0, wxEXPAND | wxBOTTOM, 2);
        $box212->Add($box2122, 0, wxEXPAND | wxTop, 2);
        
        $box21 = new wxBoxSizer(wxHORIZONTAL);
        $box21->Add($box211, 1, wxEXPAND);  // 允许缩放
        $box21->Add($box212, 1, wxEXPAND | wxLEFT, 2);  
        
        // box31: response body
        $notebook = new wxNotebook($panel, wxID_ANY);
        $lbl_res  = new wxStaticText($panel, wxID_ANY, "Response:", wxDefaultPosition, new wxSize($dwidth, 30));
        $this->txt_res_body = new wxTextCtrl($notebook, wxID_ANY, "", wxDefaultPosition, new wxSize(100, 200), wxTE_MULTILINE );
        $this->wvw_res_body = wxWebView::NewMethod($notebook, wxID_ANY);
        $this->wvw_res_body->EnableHistory(true);
        $this->txt_res_json = new wxTextCtrl($notebook, wxID_ANY, "", wxDefaultPosition, new wxSize(100, 200), wxTE_MULTILINE);
        
        $notebook->AddPage($this->txt_res_body, 'text');
        $notebook->AddPage($this->wvw_res_body, 'html');
        $notebook->AddPage($this->txt_res_json, 'json');
        
        $box31 = new wxBoxSizer(wxHORIZONTAL);
        //$box31->Add($res_body_label, 0, wxLEFT);
        $box31->Add($notebook, 1, wxEXPAND | wxLEFT, 0);
        
        $vbox  = new wxBoxSizer(wxVERTICAL);
        $vbox->Add($box11, 0, wxEXPAND | wxLEFT | wxRIGHT | wxTOP, 5);
        $vbox->Add($box12, 0, wxEXPAND | wxLEFT | wxRIGHT | wxTOP, 5);
        $vbox->Add($box21, 0, wxEXPAND | wxLEFT | wxRIGHT | wxTOP, 5);
        $vbox->Add($box31, 1, wxEXPAND | wxLEFT | wxRIGHT | wxTOP, 5);   // 设置为1允许垂直缩放
        
        $panel->SetSizer($vbox);
        
        $this->Centre();
    }
    
    function start_click()
    {
        $url = $this->ctl_url->GetValue();
        $method = HttpRequest::METHODS[$this->ctl_method->GetSelection()];
        $params = $this->ctl_req_query->getValue();
        
        $options = [
            'USE_CURL' => 1,
            'CURLOPT_SSL_VERIFYPEER' => 0,
            'CURLOPT_SSL_VERIFYHOST' => 0,
        ];
        
        $cookie = $this->ctl_req_cookie->GetValue();
        if ($cookie) {
            $options['cookie'] = $cookie;
        }
        
        $use_header = $this->opt_use_header->GetValue();
        if ($use_header) {
            $options['header'] = $this->ctl_req_header->GetValue();
        }
        
        $http = HttpRequest::request($method, $url, $params, $options);
        //print_r($http);
        
        $this->ctl_req_header->SetValue($http->getRequestHeadersString());
        $this->ctl_res_header->SetValue($http->getResponseHeadersString());
        if ($errmsg = $http->gerErrorString()) {
            $this->txt_res_body->SetValue($errmsg);
        } else {
            $text = $http->getResponseText();
            $this->ctl_req_cookie->SetValue($http->getResponseCookie());
            $this->txt_res_body->SetValue($text);
            $this->wvw_res_body->SetPage($text, $this->ctl_url->GetValue());
            if (strpos($text, '{') === 0) {
                $this->txt_res_json->SetValue(JsonHelper::dump($text));
            }
        }
        
    }
    
    function CloseWindow( $event )
    {
        $this->Close();
    }
    
}
