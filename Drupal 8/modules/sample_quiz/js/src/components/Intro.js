import React from 'react';
import PropTypes from 'prop-types';

function Intro(props) {
  return (
    <>
      <div className="quiz_slide__teaser">
        <span aria-hidden="true" className={props.introIcon}></span>
        <h2>{props.introTitle}</h2>
        <div className="image" style={{zIndex: 0}}>
          <img
            src={props.introImage.url}
            width={props.introImage.width}
            height={props.introImage.height}
            title={props.introImage.title}
            alt={props.introImage.alt}
            typeof="foaf:Image"
          />
        </div>
      </div>
      <div className="quiz_slide__content">
        <div className="content">
          <div className="content__inner" dangerouslySetInnerHTML={{__html: props.introDetails }}>
          </div>
        </div>
      </div>
      <div className="image_btn" >
        <button id="-1"  onClick={props.onAnswerSelected}>Start!</button>
      </div>
    </>
  );
}

Intro.propTypes = {
  introIcon: PropTypes.string.isRequired,
  introTitle: PropTypes.string.isRequired,
  introImage: PropTypes.object.isRequired,
  introDetails: PropTypes.string.isRequired,
  onAnswerSelected: PropTypes.func.isRequired,
};

export default Intro;
