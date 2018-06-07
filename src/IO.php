<?php
namespace marshung\io;

/**
 * 匯入匯出入口物件
 *
 * 單一工作表IO
 *
 * @example // === 匯出 ===
 *          $this->load->library('tw_ins_management/Tw_ins_management_component');
 *         
 *          // get all data
 *          $data = $this->tw_ins_management_component->empList([
 *          'select_type' => 'all',
 *          'iu_sn' => ''
 *          ]);
 *         
 *          $io = new \marshung\io\IO();
 *          $io->export($data, $config = 'AddIns', $builder = 'Excel', $style = 'Nueip');
 *         
 *          // === 匯入 ===
 *          // IO物件建構
 *          $io = new \marshung\io\IO();
 *          // 匯入處理 - 取得匯入資料
 *          $data = $io->import($config = 'AddIns', $builder = 'Excel');
 *         
 * @author Mars.Hung (tfaredxj@gmail.com) 2018-04-14
 *        
 */
class IO
{

    /**
     * 預設參數
     * 
     * @var array
     */
    protected $_options = array(
        'fileName' => 'export'
    );

    /**
     * 資料
     * 
     * @var array
     */
    protected $_data = array();

    /**
     * 結構定義物件
     * 
     * @var array
     */
    protected $_config = null;

    /**
     * 樣式定義物件
     * 
     * @var array
     */
    protected $_style = null;

    /**
     * 下拉選單定義資料
     *
     * @var array $_listMap['目標鍵名'] = array(array('value' => '數值','text' => '數值名稱'),.....);
     */
    protected $_listMap = array();

    /**
     * 建構函式 - 格式處理總成物件
     * 
     * @var object
     */
    protected $_builder = null;

    /**
     * Construct
     *
     * @throws Exception
     */
    public function __construct(Array $options = array())
    {
        // 初始化參數
        $this->_options = array_intersect_key(array_merge($this->_options, $options), $this->_options);
    }

    /**
     * Destruct
     */
    public function __destruct()
    {}

    /**
     * *********************************************
     * ************** Public Function **************
     * *********************************************
     */
    
    /**
     * 匯出處理 - 取得匯出檔
     *
     * 1.流程：傳入SQL層原始資料 => 轉換成UI層資料 => 建構匯出檔 => 匯出
     * 2.資料結構來自config object，資料樣式來自style object
     * 3.在 匯出處理+匯入處理 中，SQL層原始資料形成一個循環，是最初值，也是最終值
     */
    public function export($data, $config = 'Empty', $builder = 'Excel', $style = 'Nueip')
    {
        // 建立io物件
        $this->setBuilder($builder);
        
        // 載入資料
        $this->setData($data);
        
        // 載入定義檔
        $this->setConfig($config);
        
        // 載入Style定義
        $this->setStyle($style);
        
        // 匯出建構並輸出
        $this->exportBuilder();
    }

    /**
     * 匯入處理 - 取得匯入資料
     *
     * 1.流程：匯入 => 取得UI層原始資料 => 轉換成SQL層資料 => 傳回 SQL層資料
     * 2.資料結構來自config object
     * 3.在 匯出處理+匯入處理 中，SQL層原始資料形成一個循環，是最初值，也是最終值
     *
     * 改進可能：config名稱可存在參數工作表ConfigSheet中
     */
    public function import($config = 'Empty', $builder = 'Excel')
    {
        // 建立io物件
        $this->setBuilder($builder);
        
        // 載入定義檔
        $this->setConfig($config);
        
        // 取得上傳資料 - 將上傳檔載入IO建構物件
        $this->uploadFile2Builder();
        
        // 解析資料並回傳
        return $this->importParser();
    }

    /**
     * 參數設定
     *
     * @param string $opName
     *            參數名稱
     * @param string $opValue
     *            參數值
     * @return \marshung\io\IO
     */
    public function setOption($opName, $opValue)
    {
        $this->_options[$opName] = $opValue;
        return $this;
    }

    /**
     * 載入資料
     *
     * @param string $data
     *            資料
     */
    public function setData($data)
    {
        $this->_data = $data;
        return $this;
    }

    /**
     * 載入定義檔
     *
     * @param string $config
     *            定義檔
     */
    public function setConfig($config = 'Empty')
    {
        $this->_config = \marshung\io\ClassFactory::getConfig($config);
        return $this;
    }

    /**
     * 載入Style定義
     *
     * @param string $style
     *            IO物件
     */
    public function setStyle($style = 'Nueip')
    {
        $this->_style = \marshung\io\ClassFactory::getStyle($style);
        return $this;
    }

    /**
     * 載入下拉選單定義資料
     *
     * @param string $style
     *            IO物件
     */
    public function setList($keyName, $listDEfined)
    {
        $this->_listMap[$keyName] = $listDEfined;
        return $this;
    }

    /**
     * 建立io物件
     *
     * @param string $builder
     *            IO物件
     */
    public function setBuilder($builder = 'Excel')
    {
        $this->_builder = \marshung\io\ClassFactory::getBuilder($builder);
        return $this;
    }
    
    /**
     * 取得-參數設定
     *
     * @return array
     */
    public function getOption()
    {
        return $this->_options;
    }
    
    /**
     * 取得-資料
     *
     * @return array
     */
    public function getData()
    {
        return $this->_data;
    }
    
    /**
     * 取得-定義檔物件
     *
     * @return object
     */
    public function getConfig()
    {
        return $this->_config;
    }
    
    /**
     * 取得-Style定義物件
     *
     * @return object
     */
    public function getStyle()
    {
        return $this->_style;
    }
    
    /**
     * 取得-下拉選單定義資料
     *
     * @return array
     */
    public function getList()
    {
        return $this->_listMap;
    }
    
    /**
     * 取得-io物件
     * 
     * @return object
     */
    public function getBuilder()
    {
        return $this->_builder;
    }
    
    /**
     * ***********************************************
     * ************** Building Function **************
     * ***********************************************
     */
    
    /**
     * 匯出建構並輸出
     */
    public function exportBuilder()
    {
        // 物件檢查
        if (empty($this->_builder)) {
            $this->setBuilder();
        }
        if (empty($this->_config)) {
            $this->setConfig();
        }
        if (empty($this->_style)) {
            $this->setStyle();
        }
        
        // 載入參數
        $this->_builder->setOptions($this->_options);
        // 載入資料
        $this->_builder->setData($this->_data);
        // 載入結構定義
        $this->_builder->setConfig($this->_config);
        // 載入樣式定義
        $this->_builder->setStyle($this->_style);
        
        // 載入下拉選單定義 - 額外定義資料
        foreach ($this->_listMap as $keyName => $listDEfined) {
            $this->_config->setList($keyName, $listDEfined);
        }
        
        // 建構資料 & 輸出
        $this->_builder->build()->output();
    }

    /**
     * 匯入解析並回傳
     */
    protected function importParser()
    {
        // 載入參數
        $this->_builder->setOptions($this->_options);
        
        // 載入結構定義
        $this->_builder->setConfig($this->_config);
        
        // 載入下拉選單定義 - 額外定義資料
        foreach ($this->_listMap as $keyName => $listDEfined) {
            $this->_config->setList($keyName, $listDEfined);
        }
        
        // 建構資料 & 輸出
        return $this->_builder->parse()->getData();
    }

    /**
     * **********************************************
     * ************** Private Function **************
     * **********************************************
     */
    
    /**
     * 上傳檔處理 - 將上傳檔載入IO建構物件
     *
     * 取得上傳檔後，載入格式處理總成
     *
     * @throws Exception
     * @return array
     */
    protected function uploadFile2Builder()
    {
        // 上傳路徑
        $UploadDir = 'uploads/tmp_files/';
        if (! is_dir($UploadDir)) {
            mkdir($UploadDir, 0700);
        }
        
        // 錯誤檢查
        if (! isset($_FILES['fileupload'])) {
            throw new \Exception('File upload failed !', 400);
        }
        
        // 處理上傳檔案 - 上傳檔案只讀取一次資料就棄用，應該不需要move_uploaded_file (2018-05-02)
        $this->_builder->loadFile($_FILES['fileupload']['tmp_name'], $_FILES['fileupload']['name']);
        
        return $this;
    }
}
