<?php

/**
 * 条形码生成类
 */

class Barcodegen {
	
	private $filetype = 'PNG';	//生成图片类型
	private $dpi = 72;			//分辨率
	private $scale = 1;			//刻度宽度
	private $rotation = 0;		//图片旋转角度
	private $font_family = 'Arial.ttf';	//字体名称
	private $font_size = 8;				//字体大小
	private $text = '';					//文本内容
	private $thickness=30;				//浓度
	private $start = NULL;				//开始字符集
	private $code = 'BCGcode128';		//生成条形码类型
	
	private $class_dir = 'class';		//类文件目录
	private $font_dir = 'font';			//字体目录
	
	private $root_path;					//类目录
	
	//filetype=PNG&dpi=72&scale=1&rotation=0&font_family=Arial.ttf&font_size=8&text=%2A123456789012345%2A&
	//thickness=30&start=A&code=BCGcode128
	
	function __construct($options = array()) {
		$this->root_path = dirname(__FILE__) . DIRECTORY_SEPARATOR;
		//加载类
		require_once($this->root_path . DIRECTORY_SEPARATOR . $this->class_dir . DIRECTORY_SEPARATOR . 'BCGColor.php');
		require_once($this->root_path . DIRECTORY_SEPARATOR . $this->class_dir . DIRECTORY_SEPARATOR . 'BCGBarcode.php');
		require_once($this->root_path . DIRECTORY_SEPARATOR . $this->class_dir . DIRECTORY_SEPARATOR . 'BCGDrawing.php');
		require_once($this->root_path . DIRECTORY_SEPARATOR . $this->class_dir . DIRECTORY_SEPARATOR . 'BCGFontFile.php');
		//设置配置
		$this->set_options($options);
		
		
	}
	
	/**
	 * 设置参数
	 * @param array $options
	 */
	function set_options($options = array()) {
		foreach ($options as $key=>$value) {
			$this->$key = $value;
		}
	}
	
	/**
	 * 调用方法
	 */
	function __call($method,$params) {
		
		//检测调用方法名的有效性
		if (!preg_match('/^[A-Za-z0-9]+$/', $method)) {
			$this->show_error();
		}
		//加载每不同类型的类文件
		if (!include_once($this->root_path . $this->class_dir . DIRECTORY_SEPARATOR . $method . '.barcode.php')) {
			$this->show_error();
		}
		$filetypes = array('PNG' => BCGDrawing::IMG_FORMAT_PNG, 'JPEG' => BCGDrawing::IMG_FORMAT_JPEG, 'GIF' => BCGDrawing::IMG_FORMAT_GIF);
		//生成条形码
		$drawException = null;
		try {
			$color_black = new BCGColor(0, 0, 0);
			$color_white = new BCGColor(255, 255, 255);
		
			$code_generated = new $method();
			
			$this->baseCustomSetup($code_generated);
			$this->customSetup($code_generated, $method);
			
			$code_generated->setScale(max(1, min(4, $this->scale)));
			$code_generated->setBackgroundColor($color_white);
			$code_generated->setForegroundColor($color_black);
		
			if ($this->text !== '') {
				$text = $this->convertText($this->text);
				$code_generated->parse($text);
			}
		} catch(Exception $exception) {
			$drawException = $exception;
		}
		
		$drawing = new BCGDrawing('', $color_white);
		if($drawException) {
			$drawing->drawException($drawException);
		} else {
			$drawing->setBarcode($code_generated);
			$drawing->setRotationAngle($this->rotation);
			$drawing->setDPI($this->dpi == '' ? null : max(72, min(300, intval($this->dpi))));
			$drawing->draw();
		}
		
		switch ($this->filetype) {
			case 'PNG':
				header('Content-Type: image/png');
				break;
			case 'JPEG':
				header('Content-Type: image/jpeg');
				break;
			case 'GIF':
				header('Content-Type: image/gif');
				break;
		}
		
		$drawing->finish($filetypes[$this->filetype]);
	}
	
	/**
	 * 自定义设置
	 */
	private function customSetup(&$barcode,&$method) {
		
		switch ($method) {
			case 'BCGcode128':
			case 'BCGgs1128':
				$barcode->setStart($this->start);
			break;
			case 'BCGcode39':
			case 'BCGcode39extended':
			case 'BCGi25':
			case 'BCGmsi':
			case 'BCGs25':
				
				$barcode->setChecksum(isset($this->checksum) ? $this->checksum : FALSE);
			break;
			
			case 'BCGintelligentmail':
				if (isset($this->barcodeIdentifier) && isset($this->serviceType) && isset($this->mailerIdentifier) && isset($this->serialNumber)) {
					$barcode->setTrackingCode(intval($this->barcodeIdentifier), intval($this->serviceType), intval($this->mailerIdentifier), intval($this->serialNumber));
				}
			break;
			
			case 'BCGothercode':
				if (isset($this->label)) {
					$barcode->setLabel($this->label);
				}
			
			default:
				return ;
			break;
		}
		
	}
	
	/**
	 * 基本用户自定义设置方法
	 * @param object $barcode
	 */
	private function baseCustomSetup(&$barcode) {
	
		if (isset($this->thickness)) {
			$barcode->setThickness(max(9, min(90, intval($this->thickness))));
		}
	
		$font = 0;
		if ($this->font_family != '0' && intval($this->font_size) >= 1) {
			$font = new BCGFontFile($this->root_path . $this->font_dir . '/' . $this->font_family, intval($this->font_size));
		}
		$barcode->setFont($font);
	}
	
	
 	
	
	/**
	 * 错误显示
	 */
	private function show_error() {
		header('Content-Type: image/png');
		readfile($this->root_path . 'error.png');
		exit;
	}
	
	/**
	 * 转码
	 */
	private function convertText($text) {
   	 	$text = stripslashes($text);
   		if (function_exists('mb_convert_encoding')) {
        	$text = mb_convert_encoding($text, 'ISO-8859-1', 'UTF-8');
    	}
    	return $text;
	}
	
	
}