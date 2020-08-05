import React, { Component } from 'react';
import Quiz from './components/Quiz';
import Result from './components/Result';
import './App.css';
import Modal from 'react-modal';

class App extends Component {

  resetState() {
    jQuery.removeCookie('STYXKEY_sampleFindYourPlaceTags', { path: '/' });
    jQuery.removeCookie('sampleFindYourPlaceGivenAnswers', { path: '/' });
    this.setState (this.initialState);
    this.handleOpenModal();
    this.componentDidMount().then(() => {});
  };

  constructor(props) {
    super(props);

    jQuery.cookie.json = true;

    this.initialState = {
      props: props,
      counter: 0,
      questionId: -1,
      question: '',
      questions: [{
        nid: 0,
        question: '',
        answerOptions: [{
          tid: 0,
          name: '',
          description: '',
          weight: 0,
          image: {
            width : 0,
            height : 0,
            title : '',
            alt : '',
            url : '',
          },
        }],
        goodAnswer: -1,
        givenAnswer: -1,
      }],
      answerOptions: [{
        tid: 0,
        name: '',
        image: {
          width : 0,
          height : 0,
          title : '',
          alt : '',
          url : '',
        },
      }],
      givenAnswer: -1,
      givenAnswers: [],
      results: '',
      nid: props.nid,
      ctaText: '',
      introIcon: '',
      introTitle: '',
      introDetails:'',
      introImage: {
        width : 0,
        height : 0,
        title : '',
        alt : '',
        url : '',
      },
      image: {
        width : 0,
        height : 0,
        title : '',
        alt : '',
        url : '',
      },
      showModal: false,
    };

    this.state = this.initialState;

    Modal.setAppElement('#react_quiz_' + props.nid);

    this.handleAnswerSelected = this.handleAnswerSelected.bind(this);
    this.handleOpenModal = this.handleOpenModal.bind(this);
    this.handleCloseModal = this.handleCloseModal.bind(this);
    this.resetState = this.resetState.bind(this);
  }

  async componentDidMount() {
    let request = new XMLHttpRequest();
    let state = null;
    request.open('GET', '/node/' + this.props.nid + '?_format=json', false);
    request.send(null);
    if (request.status === 200) {
      const quiz = JSON.parse(request.responseText);
      const quizType = quiz.field_quiz_type[0].value;
      state = {
        'title': quiz.title[0].value,
        'ctaText': quiz.field_cta_text[0].value,
        'introImage': {
          'width' : quiz.field_image[0].width,
          'height' : quiz.field_image[0].height,
          'title' : quiz.field_image[0].title,
          'alt' : quiz.field_image[0].alt,
          'url' : quiz.field_image[0].url,
        },
        'introDetails': quiz.field_intro_details[0].processed,
        'introIcon': quiz.field_intro_icon[0].value,
        'introTitle': quiz.field_intro_title[0].value,
        'quizType': quiz.field_quiz_type[0].value,
        'invitation': quiz.field_invitation[0].processed,
        'resultIcon': quiz.field_result_icon[0].value,
        'resultTitle': quiz.field_result_title[0].value,
        'resultText': quiz.field_result_text[0].processed,
        'questions': quiz.field_quiz_question.map((questionReference) => {
          request.open('GET', '/entity/paragraph/' + questionReference.target_id + '?_format=json', false);
          request.send(null);
          if (request.status === 200) {
            const questionResponse = JSON.parse(request.responseText);
            let question = {};
            if (quizType == 'text') {
              question = {
                'nid': questionReference.target_id,
                'question': questionResponse.field_question[0].value,
                'goodAnswer': questionResponse.field_text_choice_good_answer[0].value,
                givenAnswer: -1,
                'answerOptions':questionResponse.field_text_choice.map((textChoiceReference) => {
                  request.open('GET', '/entity/paragraph/' + textChoiceReference.target_id + '?_format=json', false);
                  request.send(null);
                  if (request.status === 200) {
                    const response = request.responseText;
                    const textChoiceResponse = JSON.parse(response);
                    const textChoice = {
                      'name': textChoiceResponse.field_label[0].value,
                      'image': {
                        'width' : textChoiceResponse.field_image[0].width,
                        'height' : textChoiceResponse.field_image[0].height,
                        'title' : textChoiceResponse.field_image[0].title,
                        'alt' : textChoiceResponse.field_image[0].alt,
                        'url' : textChoiceResponse.field_image[0].url,
                      },
                    };
                    return textChoice;
                  } else {
                    return null;
                  }
                }),
              };
            } else {
              question = {
                'question': questionResponse.field_question[0].value,
                'answerOptions': questionResponse.field_tag_choice.map((tagChoiceReference) => {
                  request.open('GET', '/taxonomy/term/' + tagChoiceReference.target_id + '?_format=json', false);
                  request.send(null);
                  if (request.status === 200) {
                    const tagChoiceResponse = JSON.parse(request.responseText);
                    let tagChoice = {
                      'tid': tagChoiceReference.target_id,
                      'name': tagChoiceResponse.name[0].value,
                      'image': {
                        'width': typeof tagChoiceResponse.field_featured_image[0] !== 'undefined' ? tagChoiceResponse.field_featured_image[0].width : '',
                        'height': typeof tagChoiceResponse.field_featured_image[0] !== 'undefined' ? tagChoiceResponse.field_featured_image[0].height : '',
                        'title': typeof tagChoiceResponse.field_featured_image[0] !== 'undefined' ? tagChoiceResponse.field_featured_image[0].title : '',
                        'alt': typeof tagChoiceResponse.field_featured_image[0] !== 'undefined' ? tagChoiceResponse.field_featured_image[0].alt : '',
                        'url': typeof tagChoiceResponse.field_featured_image[0] !== 'undefined' ? tagChoiceResponse.field_featured_image[0].url : '',
                      },
                    };
                    return tagChoice;
                  } else {
                    return null;
                  }
                }),
              }
            }
            return question;
          } else {
            return null;
          }
        }),
      };
      state.givenAnswers = this.getGivenAnswers(state);
      state.results = this.getResults(state);
      this.setState(state);
    }
  }

  getGivenAnswers(state) {
    if (state.quizType == 'tag') {
      return jQuery.cookie('sampleFindYourPlaceGivenAnswers') ? jQuery.cookie('sampleFindYourPlaceGivenAnswers') : [];
    }
    else {
      return [];
    }
  }

  handleAnswerSelected(event) {
    this.setUserAnswer(event.currentTarget.id);

    if (this.state.counter < this.state.questions.length) {
      setTimeout(() => this.setNextQuestion(), 300);
    } else {
      setTimeout(() => this.setResults(this.getResults(this.state)), 300);
    }
  }

  setUserAnswer(answer) {
    this.setState((state, props) => ({
      givenAnswers: {
        ...state.givenAnswers,
        [state.questionId]: parseInt(answer),
      },
      givenAnswer: parseInt(answer)
    }))
  }

  setNextQuestion() {
    const counter = this.state.counter + 1;
    const questionId = this.state.questionId + 1;

    this.setState({
      counter: counter,
      questionId: questionId,
      question: this.state.questions[questionId].question,
      answerOptions: this.state.questions[questionId].answerOptions,
      image: this.state.questions[questionId].image,
      givenAnswer: this.state.questions[questionId].givenAnswer,
    });
  }

  getResults(state) {
    if (state == null) {
      state = this.state;
    }
    let givenAnswers = JSON.stringify(state.givenAnswers) != "[]" ? state.givenAnswers : this.getGivenAnswers(state);
    delete givenAnswers[-1];

    let results = null;
    if (Object.keys(givenAnswers).length > 0 && state.questions[0].answerOptions.length > 1) {
      if (state.quizType == 'tag') {
        results = (
          <>
            {state.questions.map((question, index) => (
              <p key={index}>{question.question}: {question.answerOptions[givenAnswers[index]].name}</p>
            ))}
          </>
        );
      }
      else {
        results = (
          <>
            {state.questions.map((question, index) => (
              <p key={index}>{question.question}: {question.answerOptions[givenAnswers[index]].name}</p>
            ))}
          </>
        );
      }
    }

    return results;
  }

  setResults(results) {
    this.setState({ results: results });

    delete this.state.givenAnswers[-1];

    if (this.state.quizType === 'tag') {
      let tags = [];
      for (const property in this.state.givenAnswers) {
        tags.push(this.state.questions[property].answerOptions[this.state.givenAnswers[property]].tid)
      }
      if (this.state.quizType == 'tag') {
        const cookieOptions = {
          expires: 365,
          path: '/',
          secure: true,
        };
        jQuery.cookie('STYXKEY_sampleFindYourPlaceTags', tags, cookieOptions);
        jQuery.cookie('sampleFindYourPlaceGivenAnswers', this.state.givenAnswers, cookieOptions);
      }
    }
  }

  renderQuiz() {
    return (
      <Quiz
        givenAnswer={this.state.givenAnswer}
        answerOptions={this.state.answerOptions}
        questionId={this.state.questionId + 1}
        question={this.state.question}
        onAnswerSelected={this.handleAnswerSelected}
        introIcon={this.state.introIcon}
        introImage={this.state.introImage}
        introDetails={this.state.introDetails}
        introTitle={this.state.introTitle}
      />
    );
  }

  renderResults() {
    return (
      <Result
        resultIcon={this.state.resultIcon}
        resultTitle={this.state.resultTitle}
        resultText={this.state.resultText}
        quizResults={this.state.results}
        quizType={this.state.quizType}
        onReset={this.resetState}
      />
    );
  }

  handleOpenModal() {
    this.setState({ showModal: true });
  }

  handleCloseModal() {
    this.setState({ showModal: false });
  }

  render() {
    return (
      <div className="App">
        <div className="cta_panel__teaser">
          <div className="content">
            <div className="content__inner" dangerouslySetInnerHTML={{__html: this.state.invitation }} />
          </div>
        </div>
        <div className="cta_panel__cta">
          <div className="image_btn">
            <div className="image_btn__inner">
              <a onClick={this.handleOpenModal}>
                <div className="image_btn__cta">
                  <div className="btn">
                    <h3>
                      {this.state.title}
                    </h3>
                  </div>
                </div>
                <div className="image_btn__image">
                  <img
                    src={this.state.introImage.url}
                    width={this.state.introImage.width}
                    height={this.state.introImage.height}
                    alt={this.state.introImage.alt}
                    typeof="foaf:Image"
                  />
                </div>
              </a>
            </div>
          </div>
        </div>
        <Modal
          isOpen={this.state.showModal}
          style={{
            overlay: {
              'position': 'fixed',
              'width': '100%',
              'height': '100%',
              'top': '0',
              'left': '0',
              'overflowX': 'hidden',
              'overflowY': 'auto',
              'zIndex': '100000',
              'backgroundColor': 'rgba(0, 0, 0, 0.7)',
            },
            content: {
              'position': 'static',
              'width': '100%',
              'height': '100%',
              'maxHeight': 'none',
              'backgroundColor': 'transparent',
              'padding': '30px',
              'boxSizing': 'border-box',
              'overflow': 'hidden',
            }
          }}
        >
          <div className="quiz global_width--narrow">
            <div className="quiz__inner">
              <div className="quiz__close" onClick={this.handleCloseModal}>
                <button><span aria-hidden="true" className="fal fa-times"></span></button>
              </div>
              <div className="quiz__slides">
                <span mode="out-in">
                  {this.state.results ? this.renderResults() : this.renderQuiz()}
                </span>
              </div>
            </div>
          </div>
        </Modal>
      </div>
    );
  }
}

export default App;
