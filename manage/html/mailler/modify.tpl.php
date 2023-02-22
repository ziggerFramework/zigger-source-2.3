<div id="sub-tit">
    <h2>메일 템플릿 정보 관리</h2>
    <em><i class="fa fa-exclamation-circle"></i>메일 템플릿 정보 확인 및 관리</em>
</div>

<!-- article -->
<article>
    <form <?php echo $this->form(); ?>>
        <?php echo $manage->print_hidden_inp(); ?>
        <input type="hidden" name="mode" value="mod" />
        <input type="hidden" name="idx" value="<?php echo $write['idx']; ?>" />


        <table class="table1">
            <thead>
                <tr>
                    <th colspan="2" class="tal">메일 템플릿 기본설정</th>
                </tr>
            </thead>
            <tbody>
                <th>템플릿 type</th>
                <td>
                    <strong><?php echo $write['type']; ?></strong>
                    <span class="tbl_sment">템플릿 type은 변경 불가합니다.</span>
                </td>
            </tr>
            <tr>
                <th>템플릿 설명</th>
                <td>
                    <?php if ($write['system'] == "N") { ?>
                    <input type="text" name="title" class="inp w50p" title="템플릿 설명" value="<?php echo $write['title']; ?>" />
                    <?php } else { ?>
                    <input type="hidden" name="title" value="<?php echo $write['title']; ?>" />
                    <strong><?php echo $write['title']; ?></strong>
                    <span class="tbl_sment">시스템 발송 메일 템플릿은 설명 변경이 불가합니다.</span>
                    <?php } ?>
                </td>
            </tr>
            <tr>
                <th>템플릿 내용</th>
                <td>
                    <textarea id="html" name="html" title="본문 내용"><?php echo $write['html']; ?></textarea>
                    <script type="text/javascript">CKEDITOR.replace('html');</script>
                    <span class="tbl_sment">템플릿 내용에 치환자를 사용할 수 있습니다.<br /><br />사이트명: {{site_title}}<br />메일 본문: {{article}}</span>
                </td>
            </tr>
        </tbody>
    </table>

    <div class="btn-wrap">
        <div class="center">
            <?php if ($write['system']=="N") { ?>
            <a href="#" class="btn2 mr30" data-form-before-confirm="다시 복구할 수 없습니다. 정말로 삭제 처리 하시겠습니까? => mode:del"><i class="fa fa-trash-alt"></i>템플릿 삭제</a>
            <?php } ?>
            <a href="./template<?php echo $manage->lnk_def_param(); ?>" class="btn1"><i class="fa fa-bars"></i>리스트</a>
            <button type="submit" class="btn1"><i class="fa fa-check"></i>저장</button>
        </div>
    </div>
</form>

</article>
