<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/2/25
 * Time: 14:28
 */
$data_config = array();
require_once 'data.php';
$M = mysqli_connect($data_config['DB_HOST'],$data_config['DB_USER'],$data_config['DB_PWD'],$data_config['DB_NAME'],$data_config['DB_PORT']);
if (!$mysql_connect)
{
    die('Could not connect: ' . mysqli_error());
}
$M->set_charset('utf8');
$sql .= 'select * from erp_order where id_order = 14';
$sql .= 'select * from erp_order where id_order = 20';

$M->autocommit(false);

//执行多条SQL语句
//只要这两条SQL语句都成功了，就手工提交给数据库
//否则，就回滚，撤销之前的有效操作。
$res = $M->multi_query($sql);
if ($res) {
//通过影响的行数，来判定SQL语句是否成功执行
//如果$_success是false说明sql语句有吴，那么就执行回滚，否则就手工提交
    $_success = $M->affected_rows == 1 ? true : false;
//下移指针
    $M->next_result();
    $_success2 = $M->affected_rows == 1 ? true : false;
//如果两条都成功的话
    if ($_success && $_success2) {
//执行手工提交
        $M->commit();
        echo '完美提交';
    } else {
        //执行回滚，撤销之前的所有操作
        $M->rollback();
        echo '所有操作归零！';
    }
} else {
    echo '第一条SQL语句有错误!';
}
//再开启自动提交
$M->autocommit(true);
$M->close();
$data = $res->fetch_row($sql);
die;
$data = $_POST['data'];
//导入记录到文件
$id_increment_arr = getDataRow($data);
for($i=1;$i<=30;$i++)
{
    $fp = fopen("a.txt", "w") ;
    $i = $i<10?'0'.$i:$i;
    $gz = gzopen('gz/'.$i.'_log.txt.gz',"r");
    while (!gzeof($gz)) {
        $str = gzgets($gz);
        fputs($fp, $str) ;
    }
    $str = file_get_contents('a.txt');
    $log = array(" ","　","\t","\n","\r");
    $str_new = str_replace($log, "", $str);

    $file_path = 'info.csv'; // 文件保存路径

    foreach ($id_increment_arr as $key => $id_increment)
    {
        $export_col = '';
        //进行正则匹配
        $res = pregMatch($str_new,$id_increment);
        if ($res)
        {
            $res_one = json_decode(trim(str_replace('REQUESTDATA::id_increment:'.$id_increment,'',$res[0])),true);
            $res_one['pieces'] = 0;
            $res_one['CODValue'] = 0;
            foreach ($res_one['items'] as $val)
            {
                @$res_one['pieces'] = $res_one['pieces'] +$val['pieces'];
                @$res_one['CODValue'] = $res_one['CODValue'] +$val['CODValue'];
            }
            $export_col = $id_increment."\t".','.$res_one['parcelValue']."\t".','.$res_one['pieces']."\t".','.$res_one['items'][0]['unitPrice']."\t".','.$res_one['CODValue'].'"'."\t\n" ;
            file_put_contents($file_path, chr(239).chr(187).chr(191).$export_col,FILE_APPEND);
            unset($id_increment_arr[$key]);  //去掉已经筛选到的结果
        }
        else
        {
            $export_col .= $id_increment."\t".','."\t".','."\t".','."\t".','.'"'."\t\n" ;
        }
    }
}

echo "Over";

//取出excel特殊字符，获取运单号数组
 function getDataRow($data)
{
    if (empty($data))
        return array();
    $data = preg_split("~[\r\n]~", $data, -1, PREG_SPLIT_NO_EMPTY);
    $id_increment_arr = array();
    foreach ($data as $row) {
        $row = trim($row);
        if (empty($row))
            continue;
        $row = explode("\t", trim($row), 1);
        if (count($row) != 1 || !$row[0]) {
            continue;
        }
        $id_increment = str_replace("'", '', $row[0]);
        $id_increment_arr[] = $id_increment;
    }
    return $id_increment_arr;
}

//正则匹配推送订单信息
function pregMatch($str,$id_increment){
    $rule = "/REQUESTDATA::id_increment:".$id_increment."\{.+?\}]}/";
    preg_match($rule,$str,$result);
    return $result;
}

?>
