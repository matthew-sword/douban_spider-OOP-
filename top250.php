<?php
require ('curl_get.php');   //获取html页面
require ('simple_html_dom.php'); //提取html页面
require ('conn/conn.php'); //连接数据库


class Top250
{
    public $url = 'https://movie.douban.com/top250';  //利用构造函数传参!!!!!must!!!
    //http://taoyh163.blog.163.com/blog/static/1958035620141371710957/
    public $douban_page;   //curl返回web字符串
    public $sleep_time;   //休眠时间
    public $douban_dom;  //simple_html_dom
    public $m_table;  //PDO执行返回值
    public $conn;  //PDO连接标识
    public $links; //链接表


    function scheduler()    //调度器 && url管理器
    {
        $get_douban = new GetHtml();

        //创建电影数据表  记得关闭链接！！！
        $this->m_table = $this->conn->prepare("CREATE TABLE IF NOT EXISTS top250(
                                        rank INT PRIMARY KEY AUTO_INCREMENT,
                                        link VARCHAR(100))    DEFAULT CHARSET=utf8 ");
        if ($this->m_table->execute())
            echo "table cteated...\n";

        //循环爬取list
        $i = 0;
        while($i < 10)
        {
            $this->douban_page = $get_douban->get_html($this->url);
            $this->get_list();

            //随机暂停3-5秒
            $this->sleep_time = rand(3,5);
            echo "\nsleeping($this->sleep_time s)...\n";
            sleep($this->sleep_time);

            //url生成
            $i++;
            $this->url = 'https://movie.douban.com/top250?start='.(string)($i*25).'&filter=';
        }


        //循环爬取海报，简介，基本信息，影评
        for ($i = 1;$i <= 250; $i ++)
        {
            //从mysql获取url
            $this->m_table = $this->conn->prepare("SELECT link FROM top250 WHERE rank = $i");
            $this->m_table->execute();
            $this->links = $this->m_table->fetch();

            //爬取详情页
            $this->douban_page = $get_douban->get_html($this->links[0]);

            $this->get_post($i);
            $this->get_summary($i);
            $this->get_infos($i);
            $this->get_comments($i);

            //随机暂停5-10秒
            $this->sleep_time = rand(5,10);
            echo "\nsleeping($this->sleep_time s)...\n";
            sleep($this->sleep_time);
        }

        $this->conn = null;
    }

    private function get_list()   //网页解析器 获取top250名单&链接
    {
        //打开名单文件
        $list = fopen("top_list.txt","a+");
        echo "file opened...\n";

        $rank = 0;
        $tb_writed = 0;
        $this->douban_dom = new simple_html_dom();
        $this->douban_dom->load($this->douban_page);

        //提取电影名
        $names = $this->douban_dom->find('span.title,span.other');
        $name_reg = '/&nbsp;\/.*/';

        //提取详情页链接
        $addrs = $this->douban_dom->find('a');
        $addr_reg = '/https:\/\/movie\.douban\.com\/subject.*/';

        //输出名单
        foreach ($names as $key => $value)
        {
            if (!preg_match($name_reg,$value))
            {
                echo "\n\n".$rank." ";
                fwrite($list,"\n\n"."$rank");
                $rank++;
            }

            //过滤html标签
            $value = strip_tags($value);
            $clean = str_replace('&nbsp;','',$value);

            //输出&写入
            echo $clean;
            fwrite($list,$clean);
        }

        if ($tb_writed <= 250)
        {
            //详情页url写入mysql
            foreach ($addrs as $key => $value)
            {
                //匹配电影详情页的url && 去重
                if ( preg_match($addr_reg,$value->href) &&
                    !strstr($addrs[$key]->href,$addrs[$key+1]->href) )
                {
                    $add = $value->href;
                    $this->m_table = $this->conn->prepare("INSERT INTO top250 (link) VALUES (:link)");
                    $this->m_table->bindParam(':link',$add);
                    $this->m_table->execute();

                    $tb_writed++;
                }
            }
        }
        //清空缓存&关闭文件
        $this->douban_dom->clear();
        fclose($list);
    }


    private function get_post($i)   //网页解析器 获取top250名单&链接
    {
        $this->douban_dom = new simple_html_dom();
        $this->douban_dom->load($this->douban_page);

        //提取海报url
        $posters = $this->douban_dom->find('.nbgnbg');
        //下载海报
        foreach ($posters as $value)
        {
            $src = $value->children[0]->src;

            //获取海报
            ob_start();
            readfile($src);
            $img = ob_get_contents();
            ob_end_clean();

            //保存海报
            $picname = "poster"."$i".'.jpg';
            $file_img=fopen("poster/".$picname,"w+");
            fwrite($file_img,$img);
            fclose($file_img);
        }
    }


    private function get_summary($i)  //网页解析器 获取top250名单&链接
    {
        $this->douban_dom = new simple_html_dom();
        $this->douban_dom->load($this->douban_page);

        $short = $this->douban_dom->find('div[id=link-report]');
        //保存简介
        foreach ($short as $value)
        {
            //提取简介
            $summary = $value->children[0]->innertext;
            $summary = strip_tags($summary);

            //写入文件
            $sumname = "summary"."$i".'.txt';
            $file_sum = fopen("summary/".$sumname,"a+");
            fwrite($file_sum,$summary);
            fclose($file_sum);
        }
    }


    private function get_infos($i)   //网页解析器 获取top250名单&链接
    {
        $this->douban_dom = new simple_html_dom();
        $this->douban_dom->load($this->douban_page);

        //爬取基本信息
        $infos = $this->douban_dom->find('div[id=info]');
        foreach ($infos as $value)
        {
            //获取基本信息
            $info = $value->innertext;
            $info = strip_tags($info);

            //写入文件
            $sumname = "summary"."$i".'.txt';
            $file_sum = fopen("summary/".$sumname,"a+");
            fwrite($file_sum,"\n\n\n".$info);
            fclose($file_sum);
        }

    }


    private function get_comments($i)  //网页解析器 获取top250名单&链接
    {
        $this->douban_dom = new simple_html_dom();
        $this->douban_dom->load($this->douban_page);

        //获取影评
        $comments = $this->douban_dom->find('div.comment');
        //保存影评
        foreach ($comments as $value)
        {
            //抓取影评
            $comment = $value->children[1]->innertext;


            //写入文件
            $comname = "comments"."$i".'.txt';
            $file_com = fopen("comment/".$comname,"a+");
            fwrite($file_com,$comment."\n\n");
            fclose($file_com);
        }
    }

}

?>