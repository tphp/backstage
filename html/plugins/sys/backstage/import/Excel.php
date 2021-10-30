<?php

/**
 * This file is part of the tphp/tphp library
 *
 * @link        http://github.com/tphp/tphp
 * @copyright   Copyright (c) 2021 TPHP (http://www.tphp.com)
 * @license     http://opensource.org/licenses/MIT MIT
 */

class Excel
{
    function __construct()
    {
        $this->ordA = ord('A');
    }

    private function setExportHeader($fileName, $spreadsheet)
    {
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $fileName . '.xlsx"');
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');

        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: cache, must-revalidate');
        header('Pragma: public');
        $spreadsheet->getProperties()->setCreator("Maarten Balliauw")
            ->setLastModifiedBy("Maarten Balliauw")
            ->setTitle("Office 2007 XLSX Test Document")
            ->setSubject("Office 2007 XLSX Test Document")
            ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")
            ->setKeywords("office 2007 openxml php")
            ->setCategory("Test result file");
        $spreadsheet->getDefaultStyle()->getFont()->setName('宋体')
            ->setSize(11);
    }

    /**
     * 获取单元格行名称
     * @param $ord
     * @return string
     */
    private function getCellName($ord)
    {
        $first = $ord / 26;
        if ($first >= 1) {
            $first = chr(intval($first) + $this->ordA - 1);
            $second = chr($ord % 26 + $this->ordA);
        } else {
            $first = '';
            $second = chr($ord + $this->ordA);
        }
        return $first . $second;
    }

    /**
     * 导出到xlsx格式
     * @param $field 字段信息
     * @param array $data 数据
     * @param string $title 标题说明
     * @param bool $isFixedTitle 是否固定标题，如果不固定则加入时间作为扩展文件名
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function export($field, $data = [], $title = "Default", $isFixedTitle = false)
    {
        if (empty($title)) {
            $title = 'null';
        }
        $spreadsheet = new PhpOffice\PhpSpreadsheet\Spreadsheet();
        $fileName = $title;
        if (!$isFixedTitle) {
            if (empty($title)) {
                $fileName = "";
            } else {
                $fileName .= "_";
            }
            $fileName .= date("Ymd_His") . "_" . str_pad(rand(1, 10000), 5, 0, STR_PAD_LEFT);
        }
        $this->setExportHeader($fileName, $spreadsheet);
        $newData = [];
        $fieldMax = [];
        if (!is_array($data)) {
            $data = [];
        }
        $dCot = count($data) + 1;
        foreach ($data as $key => $val) {
            foreach ($val as $k => $v) {
                $v = trim($v);
                $v = preg_replace("/<.*?>/is", "", $v);
                $newData[$key][$k] = $v;
                $vLen = (strlen($v) + mb_strlen($v)) / 2;
                if (!isset($fieldMax[$k]) || $fieldMax[$k] < $vLen) {
                    $fieldMax[$k] = $vLen;
                }
            }
        }

        $remarks = [];
        if (empty($fieldMax) && is_array($field)) {
            foreach ($field as $key => $val) {
                $key = trim($key);
                $width = 10;
                if (is_array($val)) {
                    if (empty($val['name'])) {
                        $name = $key;
                    } else {
                        $name = trim($val['name']);
                        empty($name) && $name = $key;
                    }
                    $w = $val['width'];
                    if (isset($w) && is_numeric($w)) {
                        $width = intval($w / 12);
                        if ($width < 10) {
                            $width = 10;
                        }
                    }
                } else {
                    $name = trim($val);
                    empty($name) && $name = $key;
                }
                $fieldMax[$name] = $width;
                if (!empty($val['remark'])) {
                    $remarks[$name] = $val['remark'];
                }
            }
        }

        $ord = 0;
        $chrs = [];
        $i = 0;
        foreach ($fieldMax as $key => $val) {
            $chr = $this->getCellName($ord);
            $chrs[$key] = $chr;
            if ($val < 10) {
                $val = 10;
            } elseif ($val > 60) {
                $val = 60;
            } else {
                $val++;
            }
            $fk = $field[$key];
            $spreadsheet->getActiveSheet()->getColumnDimension($chr)->setWidth($val);
            if (isset($fk)) {
                if (is_array($fk)) {
                    $fName = $fk['name'];
                    if (empty($fName)) {
                        $fName = $key;
                    }
                } else {
                    $fName = $fk;
                }
            } else {
                $fName = $key;
            }
            $spreadsheet->setActiveSheetIndex(0)->setCellValue("{$chr}1", $fName);
            if (!empty($remarks[$key])) {
                $spRemark = $spreadsheet->getActiveSheet()->getComment("{$chr}1");
                $spRemark->getText()->createTextRun($remarks[$key]);
                $spRemark->setWidth(200);
            }
            $ord++;
            $i++;
        }

        // 设置单元格为文本格式
        $spreadsheet->getActiveSheet()->getStyle("A:{$chr}")
            ->getNumberFormat()
            ->setFormatCode(PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);

        $spreadsheet->getActiveSheet()->freezePane('A2');

        if ($i > 0) {
            $lastName = $this->getCellName($ord - 1);
            $spreadsheet->getActiveSheet()->getStyle("A1:{$lastName}1")->applyFromArray(
                [
                    'font' => [
                        'color' => [
                            'rgb' => '008800'
                        ]
                    ]
                ]
            );
            // $spreadsheet->getActiveSheet()->setAutoFilter("A1:{$lastName}1"); // 筛选功能
            $spreadsheet->getActiveSheet()->getStyle("A1:{$lastName}1")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER_CONTINUOUS);
            $spreadsheet->getActiveSheet()->getStyle("A1:{$lastName}{$dCot}")->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        }

        $i = 1;
        foreach ($newData as $key => $val) {
            $i++;
            foreach ($chrs as $ck => $cv) {
                $spreadsheet->setActiveSheetIndex(0)->setCellValue("{$cv}{$i}", $val[$ck]);
            }
        }


        $spreadsheet->setActiveSheetIndex(0);
        $spreadsheet->getSheet(0)->setTitle($title);
        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        exit;
    }

    /**
     * 获取xlsx文件
     * @param $filePath 文件名
     * @param array $fields 显示字段
     * @param bool $isFieldHide 是否匹配字段键值
     * @return array
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function get($filePath, $fields = [], $isFieldHide = false)
    {
        if (!is_file($filePath)) {
            return [false, '文件不存在'];
        }

        list($status, $fileType) = $this->isExcelFile($filePath);
        if (!$status) {
            return [false, $fileType];
        }

        $reader = PhpOffice\PhpSpreadsheet\IOFactory::createReader($fileType);
        $reader->setReadDataOnly(TRUE);
        $spreadsheet = $reader->load($filePath);

        $workSheet = $spreadsheet->getActiveSheet();
        $maxRow = $workSheet->getHighestRow(); // 总行数
        if ($maxRow < 1) {
            return [true, []];
        }
        // 最大输出10000行数据
        if ($maxRow > 10000) {
            $maxRow = 10000;
        }
        $maxCol = $workSheet->getHighestColumn(); // 总列数
        $maxColIndex = PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($maxCol);

        empty($fields) && $fields = [];
        $rField = [];
        $rWidth = [];
        foreach ($fields as $key => $val) {
            $key = strtolower(trim($key));
            if (is_array($val)) {
                $vName = $val['name'];
                if (empty($vName)) {
                    $vName = $key;
                } else {
                    $vName = trim($vName);
                }
                if (isset($val['width'])) {
                    $rWidth[$vName] = $val['width'];
                }
            } else {
                $vName = trim($val);
            }
            $rField[$vName] = $key;
        }
        $fieldKv = [];
        $fieldList = [];
        for ($i = 1; $i <= $maxColIndex; $i++) {
            $name = trim($workSheet->getCellByColumnAndRow($i, 1)->getValue());
            if (empty($name)) {
                continue;
            }
            if (isset($rField[$name])) {
                $fieldKv[$i] = $rField[$name];
                $fv = [
                    'key' => $rField[$name],
                    'name' => $name
                ];
                if (isset($rWidth[$name])) {
                    $fv['width'] = $rWidth[$name];
                }
                $fieldList[] = $fv;
            } elseif ($isFieldHide) {
                $fieldKv[$i] = false;
            } else {
                $fieldKv[$i] = $name;
                $fieldList[] = [
                    'key' => $name,
                    'name' => $name
                ];
            }
        }
        $retArray = [];
        for ($row = 2; $row <= $maxRow; $row++) {
            $ra = [];
            for ($i = 1; $i <= $maxColIndex; $i++) {
                $fKv = $fieldKv[$i];
                if ($fKv === false) {
                    continue;
                }
                $ra[$fKv] = $workSheet->getCellByColumnAndRow($i, $row)->getValue();
            }
            if (empty($ra)) {
                continue;
            }
            $retArray[] = $ra;
        }
        return [true, [$fieldList, $retArray, $rField]];
    }

    /**
     * 判断是否为excel文件
     * @param string $file
     * @return array
     */
    private function isExcelFile($file = '')
    {
        $fp = fopen($file, "rb");
        $bin = fread($fp, 4); //只读2字节
        fclose($fp);
        $strInfo = @unpack("C4chars", $bin);
        $typeCode = $strInfo['chars1'] . $strInfo['chars2'] . $strInfo['chars3'] . $strInfo['chars4'];
        $errors = [
            '982035101' => '该文件已加密，请先解密文件后操作',
        ];
        $oks = [
            '807534' => 'Xlsx',
            '20820717224' => 'Xls',
        ];
        if (isset($errors[$typeCode])) {
            return [false, $errors[$typeCode]];
        }
        $ok = $oks[$typeCode];
        if (!isset($ok)) {
            return [false, '文件格式错误'];
        }
        return [true, $ok];
    }
}