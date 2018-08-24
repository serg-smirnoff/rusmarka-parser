<?php

include('simple_html_dom.php');

// ������ ������� ( ������� ��� ����� � ����� rusmarka.ru, �������� 1, ���� 2017, � ������ � id 1885 )
// parser.html?year=2017&page=1&parent=1885&PageSpeed=off
// https://www.stamp-collection.ru/parser.html?year=2017&page=1&parent=1885&PageSpeed=off

// page = 0,1,2,3,4...
// year = 2017,2018...
// parent = 1885 (����� ��������� � ������ ��� �����. ����)

// ��� ������� ������ � ����� rusmarka.ru
$year = $_GET["year"]; 

// �������� ����� rusmarka.ru
$p = $_GET["page"];

// id �������� � ������ ���������� �� ����� stamp-collection.ru
$parentID = $_GET["parent"];

// ��������� url
if ($p == 0){
    $startUrl = "http://rusmarka.ru/catalog/marka/year/".$year.".aspx";
} else {
    $startUrl = "http://rusmarka.ru/catalog/marka/year/".$year."/p/".$p.".aspx";
}

// �������� ��������
$contentPage = '';
$ch = curl_init();
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
curl_setopt($ch, CURLOPT_URL, $startUrl);
$contentPage = curl_exec($ch);
curl_close($ch);

$html = new simple_html_dom();
$html->load($contentPage);

// �������� ������� ������ �������� �������
$tableCatalog = $html->find('table.catalog');
$tableCatalog[0];

// ������ ������� ��� tv ����������
$num = $tableCatalog[0]->find('p.num');
$href = $tableCatalog[0]->find('div[style="PADDING-RIGHT: 10px; PADDING-LEFT: 125px; PADDING-BOTTOM: 10px; PADDING-TOP: 10px"] p a');

for ($i=0; $i<20; $i++){
    
    $res[$i]['num'] = $num[$i]->innertext;
    $res[$i]['alias'] = str_replace("� ", "", $res[$i]['num']);
    
    $res[$i]['title'] = $res[$i]['num'].'. '.$href[$i]->innertext;
    
    // ������ �� ���������� ��������
    $res[$i]['href'] = $href[$i]->href;

        // �������� ���������� �������� � ��������� ������� ����� ��� �����, ������, ��������, ����
        $contentPageInner = '';
        $ch2 = curl_init();
        curl_setopt($ch2, CURLOPT_HEADER, 0);
        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch2, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch2, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
        curl_setopt($ch2, CURLOPT_URL, "http://rusmarka.ru".$res[$i]['href']);
        $contentPageInner = curl_exec($ch2);
        curl_close($ch2);
        
        $htmlInner = new simple_html_dom();
        $htmlInner->load($contentPageInner);
        $htmlInnerItem = $htmlInner->find('div.text');
        
        // ��������. �������� ���������� uri + ���������� ������� + ��������� � ����� ���������������� ����
        $res[$i]['src'] = $htmlInnerItem[0]->find('#ctl00_MainColumn_ctl00_gooddetail_repParts_ctl00_part_linkImage')[0]->href;
        // ���� 
        preg_match_all('!\d+!', $htmlInnerItem[0]->find('div.cart1 p')[0], $matches);
        $res[$i]['price'] = ceil($matches[0][0] * 1.6);
        // ��������
        $res[$i]['description'] = $htmlInnerItem[0]->find('div.fullinfo p#ctl00_MainColumn_ctl00_gooddetail_repParts_ctl00_part_description')[0]->plaintext;
        // ��� ����������
        $res[$i]['perforation'] = $htmlInnerItem[0]->find("#ctl00_MainColumn_ctl00_gooddetail_repParts_ctl00_part_infoTable > tbody > tr > td")[3]->plaintext;
        // ������
        $res[$i]['format'] = $htmlInnerItem[0]->find("#ctl00_MainColumn_ctl00_gooddetail_repParts_ctl00_part_infoTable > tbody > tr > td")[4]->plaintext;
        // �����
        $res[$i]['tirazh'] = $htmlInnerItem[0]->find("#ctl00_MainColumn_ctl00_gooddetail_repParts_ctl00_part_infoTable > tbody > tr > td")[5]->plaintext;

    $url = "http://rusmarka.ru".$res[$i]['src']; 
    $res[$i]['bigPicture'] = "assets/stamps_scany/".$year."/".$res[$i]['alias'].".jpg";
    copy($url, '/var/www/stampcollection/data/www/stamp-collection.ru/'.$res[$i]['bigPicture']);

    // ������� ����� ������ � ��������� �����������
    $resource = $modx->newObject('modResource');

    // ����� �������� ���������
    $resource->set('template', 7);                      // ��������� ��� ������ ������
    $resource->set('isfolder', 0);                      // ���������, ��� ��� �� ���������   
    $resource->set('published', 1);                     // �����������
    $resource->set('createdon', time());                // ����� ��������
    $resource->set('pagetitle', $res[$i]['title']);     // ���������
    $resource->set('alias', $res[$i]['alias']);         // ���������
    $resource->setContent($message);                    // ����������
    $resource->set('parent', $parentID);                // ������������ ������
    $resource->save();                                  // ���������
    
    
    // ����� �������� tv � ��� ��������� ��������
    $newdoc = $modx->getObject('modDocument', array('pagetitle' => $res[$i]['title']));
    $id = $newdoc->get('id');

    $tv = $modx->getObject('modTemplateVar',array('name'=>'bigPicture'));
    $tv->setValue($id,$res[$i]['bigPicture']);
    $tv->save();

    $tv = $modx->getObject('modTemplateVar',array('name'=>'smallDescription'));
    $tv->setValue($id,$res[$i]['description']);
    $tv->save();

    $tv = $modx->getObject('modTemplateVar',array('name'=>'bigDescription'));
    $tv->setValue($id,$res[$i]['description']);
    $tv->save();

    $tv = $modx->getObject('modTemplateVar',array('name'=>'price'));
    $tv->setValue($id,$res[$i]['price']);
    $tv->save();

    $tv = $modx->getObject('modTemplateVar',array('name'=>'inventory'));
    $tv->setValue($id,"���� � �������");
    $tv->save();

    $tv = $modx->getObject('modTemplateVar',array('name'=>'param4'));
    $tv->setValue($id,$res[$i]['perforation']);
    $tv->save();

    $tv = $modx->getObject('modTemplateVar',array('name'=>'param5'));
    $tv->setValue($id,$res[$i]['format']);
    $tv->save();
    
    $tv = $modx->getObject('modTemplateVar',array('name'=>'param1'));
    $tv->setValue($id,$res[$i]['tirazh']);
    $tv->save();
    
    
    // ������� 
    /*
	echo 'num = '.$res[$i]['num'].'<br />';
    echo 'alias = '.$res[$i]['alias'].'<br />';
    echo 'title = '.$res[$i]['title'].'<br />';
    echo 'href = '.$res[$i]['href'].'<br />';
    echo 'src = '.$res[$i]['src'].'<br />';
    echo 'bigPicture = '.$res[$i]['bigPicture'].'<br />';
    echo 'price = '.$res[$i]['price'].'<br />';
    echo 'description = '.$res[$i]['description'].'<br />';
    echo 'perforation = '.$res[$i]['perforation'].'<br />';
    echo 'format = '.$res[$i]['format'].'<br />';
    echo 'tirazh = '.$res[$i]['tirazh'].'<br />';
    
    echo "<br />";
	*/
    
}

echo "ok"; 
