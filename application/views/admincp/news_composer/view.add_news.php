<div id="content" class="span10">

    <div>

        <ul class="breadcrumb">

            <li><a href="<?php echo $this->config->base_url . ACPURL; ?>">Home</a> <span class="divider">/</span></li>

            <li><a href="<?php echo $this->config->base_url . ACPURL; ?>/news-composer">News Composer</a> <span

                        class="divider"></li>

        </ul>

    </div>

    <div class="row-fluid">

        <div class="box span12">

            <div class="box-header well" data-original-title>

                <h2><i class="icon-edit"></i>Add News</h2>



                <div class="box-icon">

                    <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>

                </div>

            </div>

            <div class="box-content">

                <?php

                    if(isset($error)){

                        echo '<div class="alert alert-error">' . $error . '</div>';

                    }

                    if(isset($success)){

                        echo '<div class="alert alert-success">' . $success . '</div>';

                    }

                ?>

                <form class="form-horizontal" method="post"

                      action="<?php echo $this->config->base_url . ACPURL; ?>/news-composer">

                    <input type="hidden" name="add_news" value="1"/>

                    <fieldset>

                        <legend></legend>

                        <div class="control-group">

                            <label class="control-label" for="typeahead">News Title</label>



                            <div class="controls">

                                <input type="text" class="input-xlarge" name="title" id="title" value=""/>

                            </div>

                        </div>

                        <div class="control-group">

                            <label class="control-label" for="img_url">IMG URL</label>



                            <div class="controls">

                                <input type="text" class="input-xlarge" id="img_url" name="img_url" value="http://"/>

                            </div>

                        </div>

                        <div class="control-group">

                            <label class="control-label" for="news_lang">Language </label>

                            <div class="controls">

                                <select id="news_lang" name="news_lang[]" multiple>

                                    <?php

                                        $languages = $this->website->lang_list();

                                        foreach($languages as $language => $flag):

                                            echo '<option value="' . $language . '">' . $flag . '</option>' . "\n";

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

                                    <option value="1">News</option>

									<option value="2">Announcement</option>

									<option value="3">Event</option>

									<option value="4">Update</option>

                                </select>

                            </div>

                        </div>

                        <div class="control-group">

                            <label class="control-label" for="news_small">Small News</label>



                            <div class="controls">

                                <textarea class="cleditor" id="news_small" name="news_small" rows="3"></textarea>

                            </div>

                        </div>

                        <div class="control-group">

                            <label class="control-label" for="news_big">Full News</label>



                            <div class="controls">

                                <textarea class="cleditor2" id="news_big" name="news_big" rows="4"></textarea>

                            </div>

                        </div>

                        <div class="form-actions">

                            <button type="submit" class="btn btn-primary">Submit</button>

                        </div>

                    </fieldset>

                </form>

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

    <div class="row-fluid">

        <div class="box span12">

            <div class="box-header well" data-original-title>

                <h2>News List</h2>



                <div class="box-icon">

                    <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>

                </div>

            </div>

            <div class="box-content">

                <?php

                    if(isset($news['error'])){

                        echo '<div class="alert alert-info">' . $news['error'] . '</div>';

                    } else{

                        echo '<table class="table">

						  <thead>

							  <tr>

								  <th>Title</th>

								  <th>Language</th>

								  <th>Crete Date</th>

								  <th>Created by</th>

								  <th>Action</th>                                        

							  </tr>

						  </thead>   

						  <tbody>';

                        foreach($news as $key => $value){

							$languages = '';

							if(str_contains($value['lang'], ',')){

								$langs = explode(',', $value['lang']);

								foreach($langs AS $lang){

									$languages .= $this->locales->nativeByCode1($lang).', ';

								}

								$languages = substr($languages, 0, -2);

							}

							else{

								$languages.= $this->locales->nativeByCode1($value['lang']);

							}

                            echo '<tr>

								<td>' . htmlspecialchars($value['title']) . '</td>

								<td>' . htmlspecialchars($languages) . '</td>

								<td class="center">' . date(DATE_FORMAT, $value['time']) . '</td>

								<td class="center">' . htmlspecialchars($value['author']) . '</td>

								<td class="center">

									<a class="btn btn-info" href="' . $this->config->base_url . ACPURL . '/edit-news/' . $value['id'] . '">

										<i class="icon-edit icon-white"></i>  

										Edit                                            

									</a>

									<a class="btn btn-danger" href="' . $this->config->base_url . ACPURL . '/delete-news/' . $value['id'] . '">

										<i class="icon-trash icon-white"></i> 

										Delete

									</a>

								</td>  

							  </tr>';

                        }

                        echo '</tbody></table>';

                    }

                ?>

            </div>

        </div>

    </div>

</div>