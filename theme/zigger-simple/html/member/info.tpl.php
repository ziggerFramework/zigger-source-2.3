<div id="sub-tit">
	<h2><?php echo $MB['name']; ?>님의 회원 정보 관리</h2>
</div>

<div class="tblform">
    <form <?php echo $this->form(); ?>>
        <input type="hidden" name="mode" value="mdf" />

        <!-- 활동정보 -->
        <fieldset>
            <h5>활동 정보</h5>
            <table class="table_wrt">
                <colgroup>
                    <col style="width: 150px;" />
                    <col style="width: auto;" />
                </colgroup>
                <tbody>
                    <tr>
                        <th>보유 포인트</th>
                        <td>
                            <strong><?php echo $mb['mb_point']; ?></strong> Point
                            <div class="mt5">
                                <a href="<?php echo PH_DIR; ?>/member/point" class="btn2 small"><i class="fa fa-exclamation-circle"></i> 포인트 상세 내역 보기</a>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th>회원 등급</th>
                        <td>
                            <?php echo $MB['type'][$mb['mb_level']]; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>회원가입일</th>
                        <td>
                            <?php echo $mb['mb_regdate']; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>최근 로그인</th>
                        <td>
                            <?php echo $mb['mb_lately']; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>최근 로그인 IP</th>
                        <td>
                            <?php echo $mb['mb_lately_ip']; ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </fieldset>

        <!-- 기본정보 -->
        <fieldset class="mt30">
            <h5>기본 정보</h5>
            <table class="table_wrt">
                <colgroup>
                    <col style="width: 150px;" />
                    <col style="width: auto;" />
                </colgroup>
                <tbody>
                    <tr>
                        <th>아이디</th>
                        <td>
                            <?php echo $mb['mb_id']; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>프로필 이미지</th>
                        <td>
                            <?php if ($mb[0]['mb_profileimg']) { ?>
                            <div class="mb-profileimg mb10" style="background-image: url('<?php echo $mb[0]['mb_profileimg']; ?>');"></div>
                            <?php } else { ?>
                            <span class="tbltxt mb10">
                                · 현재 등록된 프로필 이미지가 없습니다. <br />
                                · 설정 가능한 이미지 최대 용량은 <strong>500Kbyte</strong> 입니다.
                            </span>
                            <?php } ?>
                            <input type="file" name="profileimg" />
                            <?php if ($mb[0]['mb_profileimg']) { ?>
                            <span class="tbltxt">· 설정 가능한 이미지 최대 용량은 <strong>500Kbyte</strong> 입니다.</span>
                            <?php } ?>
                        </td>
                    </tr>
                    <tr>
                        <th>이메일</th>
                        <td>
                            <?php
                            if ($mb['mb_email']) {
                                echo $mb['mb_email'];
                            } else {
                                echo '등록된 이메일 정보가 없습니다. 이메일 변경을 먼저 해주세요.';
                            }
                            ?>

                            <?php if ($mb['mb_email_chg']) { ?>
                                <span class="email-chg-guid">
                                    <strong><?php echo $mb['mb_email_chg']; ?></strong>로 이메일 변경 대기중입니다.<br />
                                    위 이메일로 발송된 인증 메일을 확인 하시면 이메일이 변경됩니다.<br /><br />
                                    <label><input type="checkbox" name="email_chg_cc" value="checked" /> 이메일 변경 취소</label>
                                </span>
                            <?php } ?>
                        </td>
                    </tr>
                    <tr>
                        <th>이메일 변경</th>
                        <td>
                            <input type="text" name="email" title="이메일" class="inp w100" />
                            <span class="tbltxt">
                                · 이메일 변경시에만 입력 하세요.<br />
                                · 변경된 이메일로 발송되는 인증 메일 확인 후에 변경 완료됩니다.
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th>비밀번호 변경</th>
                        <td>
                            <input type="password" name="pwd" title="비밀번호" class="inp w100" />
                            <span class="tbltxt">
                                · 비밀번호 변경시에만 입력 하세요.<br />
                                · 최소 5자~최대 50자 까지 입력
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th>비밀번호 확인</th>
                        <td>
                            <input type="password" name="pwd2" title="비밀번호 확인" class="inp w100" />
                            <span class="tbltxt">
                                · 비밀번호를 변경하는 경우 비밀번호 확인을 위해 재입력
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th><em>*</em> 이름</th>
                        <td>
                            <input type="text" name="name" title="이름" class="inp w50" value="<?php echo $mb['mb_name']; ?>" />
                        </td>
                    </tr>

                    <?php if ($siteconf['use_mb_gender'] != 'N') { ?>
                    <tr>
                        <th><em>*</em> 성별</th>
                        <td>
                            <label><input type="radio" name="gender" title="남자" value="M" <?php echo $gender_chked['M']; ?> />남자</label>
                            <label><input type="radio" name="gender" title="여자" value="F" <?php echo $gender_chked['F']; ?> />여자</label>
                        </td>
                    </tr>
                    <?php } ?>

                    <?php if ($siteconf['use_mb_phone'] != 'N') { ?>
                    <tr id="get-phone-check-wrap">
                        <th><?php if ($siteconf['use_mb_phone'] == 'Y'){ ?><em>*</em> <?php } ?>휴대전화</th>
                        <td>

                            <?php if ($siteconf['use_mb_phone'] != 'N' && $siteconf['use_phonechk'] == 'Y') { ?>
                            <p class="phone_ed mb5"><?php echo $mb['mb_phone'] ? $mb['mb_phone'] : '등록된 휴대전화 없음' ; ?> <button type="button" class="btn2 small">변경</button></p>
                            <div class="phone-chg-wrap" style="display: none;">
                                <input type="hidden" name="phone_chg" value="0" />
                                <input type="text" name="phone" title="휴대전화" class="inp w100 mb5" />
                                <?php if ($siteconf['use_phonechk'] == 'Y' && $siteconf['use_sms'] == 'Y') { ?>
                                <button type="button" class="btn1 small send-sms-code mb5">SMS 인증코드 발송</button>
                                <?php } ?>
                                <span class="tbltxt">
                                    · 하이픈(-) 없이 숫자만 입력
                                </span>

                                <div id="confirm-sms-code-wrap" style="display: none;">
                                    <input type="text" name="phone_code" id="phone_code" title="휴대전화 인증코드" placeholder="인증코드 입력" class="inp w33" />
                                    <button type="button" class="btn1 small confirm-sms-code">인증코드 입력 완료</button>
                                    <span class="tbltxt">
                                        · SMS 발송된 6자리 인증코드 입력
                                    </span>
                                </div>
                            </div>
                            <?php } ?>

                            <?php if ($siteconf['use_mb_phone'] != 'N' &&  $siteconf['use_phonechk'] == 'N') { ?>
                            <input type="text" name="phone" title="휴대전화" class="inp w100" value="<?php echo $mb['mb_phone']; ?>" />
                            <span class="tbltxt">
                                · 하이픈(-) 없이 숫자만 입력
                            </span>
                            <?php } ?>

                        </td>
                    </tr>
                    <?php } ?>

                    <?php if ($siteconf['use_mb_telephone'] != 'N') { ?>
                    <tr>
                        <th><?php if ($siteconf['use_mb_telephone'] == 'Y'){ ?><em>*</em> <?php } ?>전화번호</th>
                        <td>
                            <input type="text" name="telephone" title="전화번호" class="inp w100" value="<?php echo $mb['mb_telephone']; ?>" />
                            <span class="tbltxt">
                                · 하이픈(-) 없이 숫자만 입력
                            </span>
                        </td>
                    </tr>
                    <?php } ?>

                    <?php if ($siteconf['use_mb_address'] != 'N') { ?>
                    <tr id="get-address-search-wrap">
                        <th><?php if ($siteconf['use_mb_address'] == 'Y'){ ?><em>*</em> <?php } ?>주소</th>
                        <td>
                            <input type="text" name="address1" id="address1" title="주소 - 우편번호" value="<?php echo $mb[0]['mb_address'][0]; ?>" placeholder="우편번호" class="inp w50" />
                            <button type="button" class="btn2 small mb5 search-address-btn">주소검색</button><br />
                            <input type="text" name="address2" id="address2" title="주소 - 기본주소" value="<?php echo $mb[0]['mb_address'][1]; ?>" placeholder="기본주소" class="inp w100 mb5" />
                            <input type="text" name="address3" id="address3" title="주소 - 상세주소" value="<?php echo $mb[0]['mb_address'][2]; ?>" placeholder="상세주소" class="inp w100 mb5" />
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </fieldset>

        <div class="btn-wrap">
            <div class="left">
                <button
                type="button" class="btn2" data-form-before-confirm="정말로 탈퇴 하시겠습니까? => mode:lv">
                회원탈퇴</button>
            </div>
            <div class="right">
                <button type="submit" class="btn1"><i class="fa fa-check"></i> 정보수정</button>
            </div>
        </div>

    </form>
</div>

<script type="text/javascript">
$(function() {
    $('#get-phone-check-wrap .phone_ed button').click(function() {
        $('.phone-chg-wrap input[name=phone]').val('');
        $('.phone-chg-wrap').toggle();
    })
})
</script>
