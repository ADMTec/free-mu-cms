<div id="content" class="span10">

    <div>

        <ul class="breadcrumb">

            <li><a href="<?php echo $this->config->base_url . ACPURL; ?>">Home</a> <span class="divider">/</span></li>

            <li><a href="<?php echo $this->config->base_url . ACPURL; ?>/edit-news">Edit News</a> <span class="divider">

            </li>

        </ul>

    </div>

    <div class="row-fluid">

        <div class="box span12">

            <div class="box-header well" data-original-title>

                <h2><i class="icon-edit"></i>Edit News</h2>



                <div class="box-icon">

                    <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>

                </div>

            </div>

            <div class="box-content">

                <?php

                    if(isset($news['error'])){

                        echo '<div class="alert alert-info">' . $news['error'] . '</div>';

                    } else{

                        ?>

                        <?php

                        if(isset($error)){

                            echo '<div class="alert alert-error">' . $error . '</div>';

                        }

                        if(isset($success)){

                            echo '<div class="alert alert-success">' . $success . '</div>';

                        }

                        ?>

                        <form class="form-horizontal" method="post" action="">

                            <input type="hidden" name="edit_news" value="1"/>

                            <fieldset>

                                <legend></legend>

                                <div class="control-group">

                                    <label class="control-label" for="typeahead">News Title</label>



                                    <div class="controls">

                                        <input type="text" class="input-xlarge" name="title" id="title"

                                               value="<?php echo $news['title']; ?>"/>

                                    </div>

                                </div>

                                <div class="control-group">

                                    <label class="control-label" for="img_url">IMG URL</label>



                                    <div class="controls">

                                        <input type="text" class="input-xlarge" id="img_url" name="img_url"

                                               value="<?php echo $news['icon']; ?>"/>

                                    </div>

                                </div>

                                <div class="control-group">

                                    <label class="control-label" for="news_lang">Language</label>



                                    <div class="controls">

                                        <select id="news_lang" name="news_lang[]" multiple>

                                            <?php

                                                $languages = $this->website->lang_list();

                                                foreach($languages as $language => $flag):

													if(str_contains($news['lang'], ',')){

														$langs = explode(',', $news['lang']);

														$selected = '';

														if(in_array($language, $langs)){

															$selected = 'selected="selected"';

														}

													}

													else{

														$selected = ($news['lang'] == $language) ? 'selected="selected"' : '';

													}

                                                    echo '<option value="' . $language . '" ' . $selected . '>' . $flag . '</option>' . "\n";

                                                endforeach;

                                            ?>

                                        </select>



                                        <p class="help-block">News for which translation.</p>

                                    </div>

                                </div>

								<div class="control-group">

									<label class="control-label" for="news_type">News Type </label>

									<div class="controls">

										<select id="news_type" name="news_type">

											<option value="1" <?php if(isset($news['type']) && $news['type'] == 1){ ?>selected="selected"<?php } ?>>News</option>

											<option value="2" <?php if(isset($news['type']) && $news['type'] == 2){ ?>selected="selected"<?php } ?>>Announcement</option>

											<option value="3" <?php if(isset($news['type']) && $news['type'] == 3){ ?>selected="selected"<?php } ?>>Event</option>

											<option value="4" <?php if(isset($news['type']) && $news['type'] == 4){ ?>selected="selected"<?php } ?>>Update</option>

										</select>

									</div>

								</div>

                                <div class="control-group">

                                    <label class="control-label" for="news_small">Small News</label>



                                    <div class="controls">

                                    <textarea class="cleditor" id="news_small" name="news_small"

                                              rows="3"><?php echo $news['news_content']; ?></textarea>

                                    </div>

                                </div>

                                <div class="control-group">

                                    <label class="control-label" for="news_big">Full News</label>



                                    <div class="controls">

                                    <textarea class="cleditor2" id="news_big" name="news_big"

                                              rows="4"><?php echo $news['news_content_full']; ?></textarea>

                                    </div>

                                </div>

                                <div class="form-actions">

                                    <button type="submit" class="btn btn-primary">Submit</button>

                                </div>

                            </fieldset>

                        </form>

                        <?php

                    }

                ?>

            </div>

        </div>

    </div>

    <script>

        $(document).ready(function () {

            var editorName = $('.cleditor').attr('name');

            CKEDITOR.replace(editorName);

            var editorName2 = $('.cleditor2').attr('name');

            CKEDITOR.replace(editorName2);

        });

    </script>

</div>